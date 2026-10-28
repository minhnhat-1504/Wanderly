<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendBroadcastRequest;
use App\Services\Admin\AdminBroadcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBroadcastController extends Controller
{
    public function __construct(
        protected AdminBroadcastService $broadcastService,
    ) {}

    /**
     * Display broadcast announcements history.
     */
    public function index(Request $request): View
    {
        $filters = [
            'target_audience' => $request->query('target_audience'),
            'search' => $request->query('search'),
        ];

        $broadcasts = $this->broadcastService->list($filters);
        $stats = $this->broadcastService->getStats();

        return view('admin.broadcasts.index', compact('broadcasts', 'stats', 'filters'));
    }

    /**
     * Show form to compose a new broadcast announcement.
     */
    public function create(): View
    {
        $stats = $this->broadcastService->getStats();

        return view('admin.broadcasts.create', compact('stats'));
    }

    /**
     * Dispatch a new broadcast announcement to target users.
     */
    public function store(SendBroadcastRequest $request): RedirectResponse
    {
        $broadcast = $this->broadcastService->send(
            admin: $request->user(),
            data: $request->validated(),
        );

        return redirect()->route('admin.broadcasts.index')->with('success', __(
            'Broadcast announcement dispatched successfully to :count recipients.',
            ['count' => number_format($broadcast->recipients_count)]
        ));
    }

    /**
     * Display detailed delivery and read stats for a broadcast.
     */
    public function show(int $id): View
    {
        $data = $this->broadcastService->show($id);

        return view('admin.broadcasts.show', [
            'broadcast' => $data['broadcast'],
            'sampleRecipients' => $data['sampleRecipients'],
            'readCount' => $data['readCount'],
        ]);
    }
}
