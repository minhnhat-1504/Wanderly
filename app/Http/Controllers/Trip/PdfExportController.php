<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\PdfExportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;

class PdfExportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PdfExportService $pdfExportService,
    ) {}

    /**
     * Generate and stream the trip itinerary as a PDF file.
     * Route: GET /trips/{trip}/export-pdf
     */
    public function export(Trip $trip): Response
    {
        $this->authorize('view', $trip);

        $pdf = $this->pdfExportService->generate($trip);

        $filename = 'Wanderly_Itinerary_'.str_replace([' ', '/'], '_', $trip->name).'.pdf';

        return $pdf->download($filename);
    }
}
