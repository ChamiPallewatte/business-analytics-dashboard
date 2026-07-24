<?php

namespace App\Actions;

use App\Models\Report;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadReportAction
{
    public static function handle(Report $report): StreamedResponse|Response
    {
        $format = $report->format ?? 'csv';
        $title  = $report->title ?? 'Report';
        $data   = $report->data ?? [];

        return match ($format) {
            'excel', 'csv' => self::downloadCsv($report, $title, $data),
            'pdf'          => self::downloadPdf($report, $title, $data),
            default        => self::downloadCsv($report, $title, $data),
        };
    }

    // ---------------------------------------------------------------
    // CSV / Excel download (no extra package required)
    // ---------------------------------------------------------------
    private static function downloadCsv(Report $report, string $title, array $data): StreamedResponse
    {
        $ext      = $report->format === 'excel' ? 'xlsx' : 'csv';
        $filename = self::safeFilename($title) . '.' . $ext;

        $rows = self::buildRows($report, $data);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type'        => $report->format === 'excel'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ---------------------------------------------------------------
    // PDF download — rendered as clean HTML then sent as .pdf text
    // ---------------------------------------------------------------
    private static function downloadPdf(Report $report, string $title, array $data): Response
    {
        $filename = self::safeFilename($title) . '.pdf';
        $rows     = self::buildRows($report, $data);

        // Build an HTML table — browsers can print/save as PDF, or
        // a headless-PDF library can be wired here later.
        $html = self::renderHtmlReport($title, $report, $rows);

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=utf-8',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    // ---------------------------------------------------------------
    // Build row data from the report record
    // ---------------------------------------------------------------
    private static function buildRows(Report $report, array $data): array
    {
        $rows = [];

        // Header row
        $rows[] = ['Report Title', $report->title];
        $rows[] = ['Report Type',  ucfirst($report->type ?? 'general')];
        $rows[] = ['Format',       strtoupper($report->format ?? 'CSV')];
        $rows[] = ['Status',       ucfirst($report->status ?? 'completed')];
        $rows[] = ['Generated At', $report->created_at?->format('d M Y H:i')];
        $rows[] = ['Company',      $report->company?->name ?? 'N/A'];
        $rows[] = [];
        $rows[] = ['--- Report Data ---'];

        if (empty($data)) {
            $rows[] = ['No data available for this report.'];
        } else {
            // If data is a flat key→value map
            if (array_is_list($data)) {
                foreach ($data as $idx => $item) {
                    if (is_array($item)) {
                        if ($idx === 0) {
                            $rows[] = array_keys($item);
                        }
                        $rows[] = array_values($item);
                    } else {
                        $rows[] = [$idx + 1, $item];
                    }
                }
            } else {
                $rows[] = ['Metric', 'Value'];
                foreach ($data as $key => $value) {
                    $rows[] = [
                        ucwords(str_replace('_', ' ', $key)),
                        is_array($value) ? json_encode($value) : $value,
                    ];
                }
            }
        }

        return $rows;
    }

    // ---------------------------------------------------------------
    // HTML report for PDF view / print
    // ---------------------------------------------------------------
    private static function renderHtmlReport(string $title, Report $report, array $rows): string
    {
        $company   = htmlspecialchars($report->company?->name ?? 'N/A');
        $type      = htmlspecialchars(ucfirst($report->type ?? 'general'));
        $generated = htmlspecialchars($report->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i'));
        $titleEsc  = htmlspecialchars($title);

        $tableRows = '';
        foreach ($rows as $row) {
            $cells = implode('', array_map(fn ($c) => '<td style="padding:6px 10px;border:1px solid #ddd;">'
                . htmlspecialchars((string) $c) . '</td>', $row));
            $tableRows .= "<tr>{$cells}</tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{$titleEsc}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; color: #1e293b; }
    h1   { font-size: 22px; margin-bottom: 4px; }
    .meta{ font-size: 13px; color: #64748b; margin-bottom: 24px; }
    table{ border-collapse: collapse; width: 100%; font-size: 13px; }
    th   { background: #1e40af; color: #fff; padding: 8px 10px; text-align: left; }
    tr:nth-child(even) td { background: #f1f5f9; }
    @media print { body { margin: 20px; } }
  </style>
</head>
<body>
  <h1>📊 {$titleEsc}</h1>
  <div class="meta">
    Company: <strong>{$company}</strong> &nbsp;|&nbsp;
    Type: <strong>{$type}</strong> &nbsp;|&nbsp;
    Generated: <strong>{$generated}</strong>
  </div>
  <table>
    <tbody>{$tableRows}</tbody>
  </table>
  <script>window.onload = () => window.print();</script>
</body>
</html>
HTML;
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------
    private static function safeFilename(string $title): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $title) . '_' . now()->format('Ymd_His');
    }
}
