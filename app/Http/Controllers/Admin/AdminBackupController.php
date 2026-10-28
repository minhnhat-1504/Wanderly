<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBackupRequest;
use App\Models\Backup;
use App\Services\Admin\AdminBackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminBackupController extends Controller
{
    public function __construct(
        protected AdminBackupService $backupService
    ) {}

    /**
     * Display a listing of system backups and statistics.
     */
    public function index(): View
    {
        $backups = $this->backupService->list(15);
        $stats = $this->backupService->getStats();

        return view('admin.backups.index', compact('backups', 'stats'));
    }

    /**
     * Trigger manual creation of a backup archive.
     */
    public function store(CreateBackupRequest $request): RedirectResponse
    {
        try {
            $backup = $this->backupService->createBackup($request->user(), $request->validated('type'));

            return redirect()
                ->route('admin.backups.index')
                ->with('status', __('Backup archive :name created successfully (:size).', [
                    'name' => $backup->filename,
                    'size' => $backup->formatted_size,
                ]));
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.backups.index')
                ->with('error', __('Backup creation failed: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Download a completed backup archive.
     */
    public function download(Backup $backup): Response
    {
        return $this->backupService->download(auth()->user(), $backup);
    }

    /**
     * Delete a backup archive and its physical storage file.
     */
    public function destroy(Backup $backup): RedirectResponse
    {
        $filename = $backup->filename;
        $this->backupService->delete(auth()->user(), $backup);

        return redirect()
            ->route('admin.backups.index')
            ->with('status', __('Backup archive :name deleted successfully.', ['name' => $filename]));
    }

    /**
     * Return disaster recovery CLI instructions for a specific backup as JSON.
     */
    public function instructions(Backup $backup): JsonResponse
    {
        $instructions = $this->backupService->getRestoreInstructions($backup);

        return response()->json($instructions);
    }
}
