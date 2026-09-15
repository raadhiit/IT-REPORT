<?php

namespace App\Http\Controllers;

use App\Services\WeeklyReportAggregator;
use App\Services\WeeklyReportExcelExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeeklyReportController extends Controller
{
    /**
     * Show the activity report for the requested date range, scoped to the logged-in user's role.
     * Defaults to the current Monday–Sunday week when no range is given.
     */
    public function index(Request $request, WeeklyReportAggregator $aggregator): Response
    {
        [$start, $end] = $this->resolveRange($request);

        $report = $aggregator->build($request->user(), $start, $end);

        return Inertia::render('reports/Weekly', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            ...$report,
        ]);
    }

    /**
     * Download the requested date range's activity report as a PDF.
     */
    public function pdf(Request $request, WeeklyReportAggregator $aggregator): HttpResponse
    {
        [$start, $end] = $this->resolveRange($request);

        $report = $aggregator->build($request->user(), $start, $end);

        $pdf = Pdf::loadView('reports.weekly-pdf', [
            'start' => $start->toFormattedDateString(),
            'end' => $end->toFormattedDateString(),
            'generatedAt' => CarbonImmutable::now()->toFormattedDateString(),
            'generatedBy' => $request->user()->name,
            'dailyCounts' => $aggregator->dailyCounts($request->user(), $start, $end),
            ...$report,
        ]);

        return $pdf->download("laporan-mingguan-{$start->toDateString()}_{$end->toDateString()}.pdf");
    }

    /**
     * Download the requested date range's activity report as an Excel workbook.
     */
    public function excel(Request $request, WeeklyReportAggregator $aggregator, WeeklyReportExcelExporter $exporter): StreamedResponse
    {
        [$start, $end] = $this->resolveRange($request);

        $report = $aggregator->build($request->user(), $start, $end);

        $spreadsheet = $exporter->build($report, $start->toFormattedDateString(), $end->toFormattedDateString(), $request->user()->name);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, "laporan-mingguan-{$start->toDateString()}_{$end->toDateString()}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Resolve the report's date range from `from`/`to` query params, falling back to the
     * current Monday–Sunday week when either is missing or invalid.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveRange(Request $request): array
    {
        [$defaultFrom, $defaultTo] = WeeklyReportAggregator::currentWeek();

        $from = WeeklyReportAggregator::parseDate($request->query('from')) ?? $defaultFrom;
        $to = WeeklyReportAggregator::parseDate($request->query('to')) ?? $defaultTo;

        return $from->lte($to) ? [$from, $to] : [$to, $from];
    }
}
