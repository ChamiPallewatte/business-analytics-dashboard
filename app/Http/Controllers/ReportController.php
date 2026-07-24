<?php

namespace App\Http\Controllers;

use App\Actions\DownloadReportAction;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Download the report file (CSV / Excel / PDF).
     */
    public function download(Report $report, Request $request)
    {
        $this->authorizeAccess($report, $request);

        return DownloadReportAction::handle($report);
    }

    /**
     * Preview the report as a printable HTML page (PDF format only).
     */
    public function preview(Report $report, Request $request)
    {
        $this->authorizeAccess($report, $request);

        // Force HTML view for preview regardless of stored format
        $previewReport         = clone $report;
        $previewReport->format = 'pdf';

        return DownloadReportAction::handle($previewReport);
    }

    /**
     * Authorize: Super Admin can access all reports; company users can only access own company's reports.
     */
    private function authorizeAccess(Report $report, Request $request): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return; // Super Admin can access all reports
        }

        if ((int) $report->company_id !== (int) $user->company_id) {
            abort(403, 'You do not have permission to access this report.');
        }
    }
}
