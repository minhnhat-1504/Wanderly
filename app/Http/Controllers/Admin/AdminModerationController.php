<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Requests\Admin\ToggleHideContentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Services\Admin\AdminModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminModerationController extends Controller
{
    public function __construct(
        protected AdminModerationService $moderationService,
    ) {}

    /**
     * Display listing of moderation reports with filters.
     */
    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->query('status', 'pending'),
            'reason' => $request->query('reason'),
            'target_type' => $request->query('target_type'),
            'search' => $request->query('search'),
        ];

        $reports = $this->moderationService->listReports($filters);
        $stats = $this->moderationService->getStats();

        return view('admin.moderation.index', compact('reports', 'stats', 'filters'));
    }

    /**
     * Display detailed view of a report.
     */
    public function show(Report $report): View
    {
        $report->load(['reporter', 'admin', 'reportable']);

        return view('admin.moderation.show', compact('report'));
    }

    /**
     * Resolve a report with specific action.
     */
    public function resolve(ResolveReportRequest $request, Report $report): RedirectResponse
    {
        $this->moderationService->resolveReport(
            admin: $request->user(),
            report: $report,
            action: $request->validated('action'),
            adminNote: $request->validated('admin_note'),
        );

        return back()->with('success', __('Report has been processed and resolution applied.'));
    }

    /**
     * Dismiss a report without penalizing the target content.
     */
    public function dismiss(Request $request, Report $report): RedirectResponse
    {
        $this->moderationService->dismissReport(
            admin: $request->user(),
            report: $report,
            adminNote: $request->input('admin_note'),
        );

        return back()->with('success', __('Report has been dismissed without action on content.'));
    }

    /**
     * Content moderation management listing (Posts and Comments).
     */
    public function content(Request $request): View
    {
        $type = $request->query('type', 'posts');
        $filters = [
            'status' => $request->query('status', 'all'),
            'search' => $request->query('search'),
        ];

        if ($type === 'comments') {
            $items = $this->moderationService->listComments($filters);
        } else {
            $items = $this->moderationService->listPosts($filters);
        }

        $stats = $this->moderationService->getStats();

        return view('admin.moderation.content', compact('items', 'stats', 'filters', 'type'));
    }

    /**
     * Toggle visibility of a post directly.
     */
    public function toggleHidePost(ToggleHideContentRequest $request, Post $post): RedirectResponse
    {
        $this->moderationService->toggleHidePost(
            admin: $request->user(),
            post: $post,
            reason: $request->validated('reason'),
        );

        $statusMessage = $post->is_hidden
            ? __('Post has been hidden from the community feed.')
            : __('Post visibility has been restored.');

        return back()->with('success', $statusMessage);
    }

    /**
     * Toggle visibility of a comment directly.
     */
    public function toggleHideComment(ToggleHideContentRequest $request, Comment $comment): RedirectResponse
    {
        $this->moderationService->toggleHideComment(
            admin: $request->user(),
            comment: $comment,
            reason: $request->validated('reason'),
        );

        $statusMessage = $comment->is_hidden
            ? __('Comment has been hidden.')
            : __('Comment visibility has been restored.');

        return back()->with('success', $statusMessage);
    }
}
