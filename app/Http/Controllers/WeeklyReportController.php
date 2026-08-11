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
     * Show the current week's activity report, scoped to the logged-in user's role.
     */
    public function index(Request $request, WeeklyReportAggregator $aggregator): Response
    {
        [$start, $end] = WeeklyReportAggregator::currentWeek();

        $report = $aggregator->build($request->user(), $start, $end);

        return Inertia::render('reports/Weekly', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            ...$report,
        ]);
    }

    /**
     * Download the current week's activity report as a PDF.
     */
    public function pdf(Request $request, WeeklyReportAggregator $aggregator): HttpResponse
    {
        [$start, $end] = WeeklyReportAggregator::currentWeek();

        $report = $aggregator->build($request->user(), $start, $end);

        $pdf = Pdf::loadView('reports.weekly-pdf', [
            'start' => $start->toFormattedDateString(),
            'end' => $end->toFormattedDateString(),
            'generatedAt' => CarbonImmutable::now()->toFormattedDateString(),
            'generatedBy' => $request->user()->name,
            ...$report,
        ]);

        return $pdf->download("laporan-mingguan-{$start->toDateString()}.pdf");
    }

    /**
     * Download the current week's activity report as an Excel workbook.
     */
    public function excel(Request $request, WeeklyReportAggregator $aggregator, WeeklyReportExcelExporter $exporter): StreamedResponse
    {
        [$start, $end] = WeeklyReportAggregator::currentWeek();

        $report = $aggregator->build($request->user(), $start, $end);

        $spreadsheet = $exporter->build($report, $start->toFormattedDateString(), $end->toFormattedDateString(), $request->user()->name);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, "laporan-mingguan-{$start->toDateString()}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
