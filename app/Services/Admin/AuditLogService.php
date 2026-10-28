<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * Record an administrative audit log entry.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function record(
        User $admin,
        string $action,
        ?Model $subject = null,
        array $old = [],
        array $new = [],
        ?string $ip = null
    ): AuditLog {
        return AuditLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'old_values' => ! empty($old) ? $old : null,
            'new_values' => ! empty($new) ? $new : null,
            'ip_address' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Fetch paginated audit logs with search and date filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::with(['admin', 'subject'])->latest();

        if (! empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', 'like', '%'.$filters['action'].'%');
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get recent audit activities for dashboard feed.
     *
     * @return Collection<int, AuditLog>
     */
    public function getRecent(int $limit = 10)
    {
        return AuditLog::with(['admin', 'subject'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
