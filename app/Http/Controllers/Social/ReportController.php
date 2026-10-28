<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitReportRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Services\Admin\AdminModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected AdminModerationService $moderationService,
    ) {}

    /**
     * Submit report for a community post.
     */
    public function reportPost(SubmitReportRequest $request, Post $post): JsonResponse|RedirectResponse
    {
        $report = $this->moderationService->submitReport(
            reporter: $request->user(),
            target: $post,
            reason: $request->validated('reason'),
            details: $request->validated('details'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Your report has been submitted for review. Thank you for keeping our community safe.'),
                'report_id' => $report->id,
            ]);
        }

        return back()->with('success', __('Your report has been submitted for review. Thank you for keeping our community safe.'));
    }

    /**
     * Submit report for a comment.
     */
    public function reportComment(SubmitReportRequest $request, Comment $comment): JsonResponse|RedirectResponse
    {
        $report = $this->moderationService->submitReport(
            reporter: $request->user(),
            target: $comment,
            reason: $request->validated('reason'),
            details: $request->validated('details'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Your report has been submitted for review. Thank you for keeping our community safe.'),
                'report_id' => $report->id,
            ]);
        }

        return back()->with('success', __('Your report has been submitted for review. Thank you for keeping our community safe.'));
    }

    /**
     * Display status and moderator resolution details for a submitted report.
     */
    public function showReport(Request $request, Report $report): View
    {
        // Only the reporter or an admin may view this report
        if ($report->reporter_id !== $request->user()->id && $request->user()->role !== 'admin') {
            abort(403, __('You are not authorized to view this report.'));
        }

        $report->load(['reportable', 'admin', 'reporter']);

        return view('social.report-status', compact('report'));
    }
}
