<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitReportRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\Admin\AdminModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

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
}
