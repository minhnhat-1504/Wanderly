<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Get paginated list of users with optional filtering.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $status = $filters['status'] ?? 'all';
        $role = $filters['role'] ?? null;
        $search = isset($filters['search']) ? trim($filters['search']) : null;

        $query = User::query();

        if ($status === 'trashed') {
            $query->onlyTrashed();
        } elseif ($status === 'suspended') {
            $query->where('is_suspended', true);
        } elseif ($status === 'active') {
            $query->where('is_suspended', false);
        }

        if (! empty($role) && in_array($role, ['traveler', 'provider', 'admin'])) {
            $query->where('role', $role);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return $query->withCount(['trips', 'posts', 'comments'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get aggregate user count statistics for filter tabs.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return [
            'total' => User::count(),
            'travelers' => User::where('role', 'traveler')->count(),
            'providers' => User::where('role', 'provider')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'suspended' => User::where('is_suspended', true)->count(),
            'trashed' => User::onlyTrashed()->count(),
        ];
    }

    /**
     * Find user with details, stats and related audit logs.
     *
     * @return array{user: User, bookingsCount: int, auditLogs: Collection<int, AuditLog>}
     */
    public function show(int $id): array
    {
        $user = User::withTrashed()
            ->withCount(['trips', 'posts', 'comments'])
            ->findOrFail($id);

        $bookingsCount = 0;
        if (class_exists(Booking::class)) {
            $bookingsCount = Booking::where(function ($q) use ($user) {
                $q->whereHas('trip', fn ($sq) => $sq->where('owner_id', $user->id));
                if ($user->provider) {
                    $q->orWhere('provider_id', $user->provider->id);
                }
            })->count();
        }

        $auditLogs = AuditLog::with('admin')
            ->where(function ($q) use ($user) {
                $q->where(function ($sq) use ($user) {
                    $sq->where('subject_type', User::class)
                        ->where('subject_id', $user->id);
                })->orWhere('admin_id', $user->id);
            })
            ->latest('id')
            ->limit(20)
            ->get();

        return [
            'user' => $user,
            'bookingsCount' => $bookingsCount,
            'auditLogs' => $auditLogs,
        ];
    }

    /**
     * Change user role.
     *
     * @throws ValidationException
     */
    public function changeRole(User $admin, User $target, string $newRole): User
    {
        if ($admin->id === $target->id) {
            throw ValidationException::withMessages([
                'role' => __('You cannot change your own role.'),
            ]);
        }

        if ($target->role === 'admin' && $newRole !== 'admin') {
            $adminCount = User::where('role', 'admin')->whereNull('deleted_at')->count();
            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => __('Cannot demote the only remaining administrator.'),
                ]);
            }
        }

        return DB::transaction(function () use ($admin, $target, $newRole) {
            $oldRole = $target->role;

            $target->role = $newRole;
            $target->save();

            $this->auditLogService->record(
                admin: $admin,
                action: 'user.change_role',
                subject: $target,
                oldValues: ['role' => $oldRole],
                newValues: ['role' => $newRole],
                metadata: ['target_email' => $target->email]
            );

            $this->notificationService->notifyRoleChanged($target, $newRole);

            return $target;
        });
    }

    /**
     * Suspend a user with required reason and invalidate active sessions.
     *
     * @throws ValidationException
     */
    public function suspend(User $admin, User $target, string $reason): User
    {
        if ($admin->id === $target->id) {
            throw ValidationException::withMessages([
                'reason' => __('You cannot suspend your own account.'),
            ]);
        }

        if ($target->role === 'admin') {
            $activeAdminCount = User::where('role', 'admin')
                ->whereNull('deleted_at')
                ->where('is_suspended', false)
                ->count();

            if ($activeAdminCount <= 1) {
                throw ValidationException::withMessages([
                    'reason' => __('Cannot suspend the only remaining active administrator.'),
                ]);
            }
        }

        return DB::transaction(function () use ($admin, $target, $reason) {
            $oldValues = [
                'is_suspended' => $target->is_suspended,
                'suspended_reason' => $target->suspended_reason,
            ];

            $target->is_suspended = true;
            $target->suspended_reason = $reason;
            $target->suspended_at = now();
            $target->save();

            // Invalidate user sessions from database if sessions table exists
            if (DB::getSchemaBuilder()->hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }

            $this->auditLogService->record(
                admin: $admin,
                action: 'user.suspend',
                subject: $target,
                oldValues: $oldValues,
                newValues: [
                    'is_suspended' => true,
                    'suspended_reason' => $reason,
                    'suspended_at' => $target->suspended_at->toIso8601String(),
                ],
                metadata: ['reason' => $reason, 'target_email' => $target->email]
            );

            $this->notificationService->notifyAccountSuspended($target, $reason);

            return $target;
        });
    }

    /**
     * Unsuspend a previously suspended user.
     */
    public function unsuspend(User $admin, User $target): User
    {
        return DB::transaction(function () use ($admin, $target) {
            $oldValues = [
                'is_suspended' => $target->is_suspended,
                'suspended_reason' => $target->suspended_reason,
            ];

            $target->is_suspended = false;
            $target->suspended_reason = null;
            $target->suspended_at = null;
            $target->save();

            $this->auditLogService->record(
                admin: $admin,
                action: 'user.unsuspend',
                subject: $target,
                oldValues: $oldValues,
                newValues: [
                    'is_suspended' => false,
                    'suspended_reason' => null,
                    'suspended_at' => null,
                ],
                metadata: ['target_email' => $target->email]
            );

            $this->notificationService->notifyAccountUnsuspended($target);

            return $target;
        });
    }

    /**
     * Soft delete a user account and invalidate sessions.
     *
     * @throws ValidationException
     */
    public function softDelete(User $admin, User $target): bool
    {
        if ($admin->id === $target->id) {
            throw ValidationException::withMessages([
                'user' => __('You cannot delete your own account.'),
            ]);
        }

        if ($target->role === 'admin') {
            $adminCount = User::where('role', 'admin')->whereNull('deleted_at')->count();
            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'user' => __('Cannot delete the only remaining administrator.'),
                ]);
            }
        }

        return DB::transaction(function () use ($admin, $target) {
            if (DB::getSchemaBuilder()->hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }

            $targetEmail = $target->email;
            $target->delete();

            $this->auditLogService->record(
                admin: $admin,
                action: 'user.delete',
                subject: $target,
                oldValues: ['deleted_at' => null],
                newValues: ['deleted_at' => now()->toIso8601String()],
                metadata: ['target_email' => $targetEmail]
            );

            return true;
        });
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $admin, int $targetId): User
    {
        return DB::transaction(function () use ($admin, $targetId) {
            $target = User::onlyTrashed()->findOrFail($targetId);
            $target->restore();

            $this->auditLogService->record(
                admin: $admin,
                action: 'user.restore',
                subject: $target,
                oldValues: ['deleted_at' => 'trashed'],
                newValues: ['deleted_at' => null],
                metadata: ['target_email' => $target->email]
            );

            return $target;
        });
    }
}
