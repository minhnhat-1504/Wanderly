<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeRoleRequest;
use App\Http\Requests\Admin\SuspendUserRequest;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(
        protected AdminUserService $userService,
    ) {}

    /**
     * Display a paginated listing of users.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->query('search'),
            'role' => $request->query('role'),
            'status' => $request->query('status', 'all'),
        ];

        $users = $this->userService->list($filters);
        $stats = $this->userService->getStats();

        return view('admin.users.index', compact('users', 'stats', 'filters'));
    }

    /**
     * Display detailed profile and history for a single user.
     */
    public function show(int $id): View
    {
        $data = $this->userService->show($id);

        return view('admin.users.show', [
            'user' => $data['user'],
            'bookingsCount' => $data['bookingsCount'],
            'auditLogs' => $data['auditLogs'],
        ]);
    }

    /**
     * Update the role of the specified user.
     */
    public function changeRole(ChangeRoleRequest $request, User $user): RedirectResponse
    {
        $this->userService->changeRole(
            admin: $request->user(),
            target: $user,
            newRole: $request->validated('role'),
        );

        return back()->with('success', __('User role updated to :role successfully.', [
            'role' => ucfirst($request->validated('role')),
        ]));
    }

    /**
     * Suspend a user account with mandatory reason.
     */
    public function suspend(SuspendUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->suspend(
            admin: $request->user(),
            target: $user,
            reason: $request->validated('reason'),
        );

        return back()->with('success', __('User account has been suspended and active sessions terminated.'));
    }

    /**
     * Unsuspend a suspended user account.
     */
    public function unsuspend(Request $request, User $user): RedirectResponse
    {
        $this->userService->unsuspend(
            admin: $request->user(),
            target: $user,
        );

        return back()->with('success', __('User account suspension has been lifted.'));
    }

    /**
     * Soft delete a user account.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->userService->softDelete(
            admin: $request->user(),
            target: $user,
        );

        return back()->with('success', __('User account has been deleted.'));
    }

    /**
     * Restore a soft-deleted user account.
     */
    public function restore(Request $request, int $id): RedirectResponse
    {
        $this->userService->restore(
            admin: $request->user(),
            targetId: $id,
        );

        return back()->with('success', __('User account has been successfully restored.'));
    }
}
