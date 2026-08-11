<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @phpstan-type Report array{
 *     total: int,
 *     byCategory: Collection<int, array{value: string, label: string, count: int}>,
 *     byStaff: Collection<int, array{id: int, name: string, total: int, byCategory: Collection<int, array{value: string, count: int}>}>,
 *     detailsByCategory: Collection<int, array{value: string, label: string, activities: Collection<int, array{id: int, tanggal: string, deskripsi: string, staff: string}>}>,
 * }
 */
class WeeklyReportExcelExporter
{
    private const HEADER_FILL = 'FF0F172A';

    private const HEADER_FONT = 'FFFFFFFF';

    /**
     * Build the weekly report as a styled, ready-to-send spreadsheet with a native Excel dashboard.
     *
     * @param  Report  $report
     */
    public function build(array $report, string $start, string $end, string $generatedBy): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $ranges = $this->buildSummarySheet($spreadsheet->getActiveSheet(), $report, $start, $end, $generatedBy);
        $this->buildDetailSheet($spreadsheet->createSheet(), $report);
        $this->buildDashboardSheet($spreadsheet->createSheet(0), $ranges);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  Report  $report
     * @return array{categoryLabels: string, categoryValues: string, staffLabels: ?string, staffValues: ?string}
     */
    private function buildSummarySheet(Worksheet $sheet, array $report, string $start, string $end, string $generatedBy): array
    {
        $sheet->setTitle('Ringkasan');

        $sheet->setCellValue('A1', "Laporan Aktivitas Mingguan — {$generatedBy}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', "{$start} – {$end}");
        $sheet->setCellValue('A3', "Total aktivitas: {$report['total']}");

        $row = 5;
        $sheet->setCellValue("A{$row}", 'Kategori');
        $sheet->setCellValue("B{$row}", 'Jumlah');
        $this->styleHeaderRow($sheet, "A{$row}:B{$row}");
        $row++;

        $categoryFirstRow = $row;
        foreach ($report['byCategory'] as $category) {
            $sheet->setCellValue("A{$row}", $category['label']);
            $sheet->setCellValue("B{$row}", $category['count']);
            $row++;
        }
        $categoryLastRow = $row - 1;

        $staffLabels = null;
        $staffValues = null;

        if ($report['byStaff']->isNotEmpty()) {
            $row++;
            $sheet->setCellValue("A{$row}", 'Staff');
            $sheet->setCellValue("B{$row}", 'Total Aktivitas');
            $this->styleHeaderRow($sheet, "A{$row}:B{$row}");
            $row++;

            $staffFirstRow = $row;
            foreach ($report['byStaff'] as $staff) {
                $sheet->setCellValue("A{$row}", $staff['name']);
                $sheet->setCellValue("B{$row}", $staff['total']);
                $row++;
            }
            $staffLastRow = $row - 1;

            $staffLabels = "'Ringkasan'!\$A\${$staffFirstRow}:\$A\${$staffLastRow}";
            $staffValues = "'Ringkasan'!\$B\${$staffFirstRow}:\$B\${$staffLastRow}";
        }

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(16);

        return [
            'categoryLabels' => "'Ringkasan'!\$A\${$categoryFirstRow}:\$A\${$categoryLastRow}",
            'categoryValues' => "'Ringkasan'!\$B\${$categoryFirstRow}:\$B\${$categoryLastRow}",
            'staffLabels' => $staffLabels,
            'staffValues' => $staffValues,
        ];
    }

    /**
     * @param  Report  $report
     */
    private function buildDetailSheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Detail');

        $sheet->setCellValue('A1', 'Tanggal');
        $sheet->setCellValue('B1', 'Staff');
        $sheet->setCellValue('C1', 'Kategori');
        $sheet->setCellValue('D1', 'Deskripsi');
        $this->styleHeaderRow($sheet, 'A1:D1');

        $row = 2;

        foreach ($report['detailsByCategory'] as $category) {
            foreach ($category['activities'] as $activity) {
                $sheet->setCellValue("A{$row}", $activity['tanggal']);
                $sheet->setCellValue("B{$row}", $activity['staff']);
                $sheet->setCellValue("C{$row}", $category['label']);
                $sheet->setCellValue("D{$row}", $activity['deskripsi']);
                $row++;
            }
        }

        if ($row > 2) {
            $sheet->setAutoFilter('A1:D'.($row - 1));
        }

        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(60);
    }

    /**
     * @param  array{categoryLabels: string, categoryValues: string, staffLabels: ?string, staffValues: ?string}  $ranges
     */
    private function buildDashboardSheet(Worksheet $sheet, array $ranges): void
    {
        $sheet->setTitle('Dashboard');

        $sheet->setCellValue('A1', 'Dashboard — Laporan Aktivitas Mingguan');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->addChart($this->makeBarChart(
            'categoryChart',
            'Distribusi Kategori',
            $ranges['categoryLabels'],
            $ranges['categoryValues'],
            'A3',
            'J20',
        ));

        if ($ranges['staffLabels'] !== null && $ranges['staffValues'] !== null) {
            $sheet->addChart($this->makeBarChart(
                'staffChart',
                'Aktivitas per Staff',
                $ranges['staffLabels'],
                $ranges['staffValues'],
                'A22',
                'J39',
            ));
        }
    }

    private function makeBarChart(
        string $name,
        string $title,
        string $labelRange,
        string $valueRange,
        string $topLeftCell,
        string $bottomRightCell,
    ): Chart {
        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $labelRange, null, 5)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $valueRange, null, 5)];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            [],
            $categories,
            $values,
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);

        $chart = new Chart($name, new Title($title), $legend, $plotArea);
        $chart->setTopLeftPosition($topLeftCell);
        $chart->setBottomRightPosition($bottomRightCell);

        return $chart;
    }

    private function styleHeaderRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setARGB(self::HEADER_FONT);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::HEADER_FILL);
    }
}
