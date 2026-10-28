<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Display a filtered list of system audit logs.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['admin_id', 'action', 'date_from', 'date_to']);
        $logs = $this->auditLogService->list($filters, 20);
        $admins = User::where('role', 'admin')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', compact('logs', 'admins', 'filters'));
    }
}
