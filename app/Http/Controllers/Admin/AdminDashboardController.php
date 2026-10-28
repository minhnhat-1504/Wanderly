<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\Admin\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Display the main administrator dashboard with 4 KPI cards and activity feed.
     */
    public function index(): View
    {
        $totalUsers = User::count();
        $newUsers7d = User::where('created_at', '>=', now()->subDays(7))->count();
        $pendingReports = Report::where('status', 'pending')->count();

        // Safely check if backups table exists for early stages
        $latestBackup = Schema::hasTable('backups')
            ? DB::table('backups')->where('status', 'completed')->latest('completed_at')->first()
            : null;

        $recentActivities = $this->auditLogService->getRecent(10);

        return view('admin.dashboard', compact(
            'totalUsers',
            'newUsers7d',
            'pendingReports',
            'latestBackup',
            'recentActivities'
        ));
    }
}
