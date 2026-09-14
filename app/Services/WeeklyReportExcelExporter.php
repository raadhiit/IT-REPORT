<?php

namespace App\Services;

use App\Enums\ActivityCategory;
use App\Enums\ActivityStatus;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalDataBar;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormatValueObject;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @phpstan-type Report array{
 *     total: int,
 *     byCategory: Collection<int, array{value: string, label: string, count: int}>,
 *     byStatus: Collection<int, array{value: string, label: string, count: int}>,
 *     byStaff: Collection<int, array{id: int, name: string, total: int, byCategory: Collection<int, array{value: string, count: int}>, byStatus: array<string, int>}>,
 *     detailsByCategory: Collection<int, array{value: string, label: string, activities: Collection<int, array{id: int, tanggal: string, deskripsi: string, staff: string, status: string}>}>,
 *     projects: array<int, array{id: int, deskripsi: string, staff: string, progress_percent: int|null, target_selesai: string|null, status: string}>,
 * }
 */
class WeeklyReportExcelExporter
{
    private const HEADER_FILL = 'FF0F172A';

    private const HEADER_FILL_HEX = '0F172A';

    private const HEADER_FONT = 'FFFFFFFF';

    /** Same palette used across the app (PDF export, activity badges) — keeps every surface visually consistent. */
    private const CATEGORY_COLORS = [
        'maintenance' => 'B45309',
        'project' => '1D4ED8',
        'support' => '0F766E',
        'meeting' => '7E22CE',
        'other' => '57534E',
    ];

    private const STATUS_COLORS = [
        'selesai' => '15803D',
        'on_track' => 'A3720A',
        'pending' => 'B3123F',
    ];

    /**
     * Build the weekly report as a styled, ready-to-send spreadsheet with a native Excel dashboard.
     *
     * @param  Report  $report
     */
    public function build(array $report, string $start, string $end, string $generatedBy): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $ranges = $this->buildSummarySheet($spreadsheet->getActiveSheet(), $report, $start, $end, $generatedBy);
        $this->buildProjectsSheet($spreadsheet->createSheet(), $report);
        $this->buildDetailSheet($spreadsheet->createSheet(), $report);
        $this->buildDashboardSheet($spreadsheet->createSheet(0), $ranges);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  Report  $report
     * @return array{statusLabels: string, statusValues: string, categoryLabels: string, categoryValues: string, staffLabels: ?string, staffValues: ?string}
     */
    private function buildSummarySheet(Worksheet $sheet, array $report, string $start, string $end, string $generatedBy): array
    {
        $sheet->setTitle('Ringkasan');

        $sheet->setCellValue('A1', "Laporan Aktivitas Mingguan — {$generatedBy}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', "{$start} – {$end}");
        $sheet->setCellValue('A3', "Total aktivitas: {$report['total']}");

        $row = 5;
        $sheet->setCellValue("A{$row}", 'Status');
        $sheet->setCellValue("B{$row}", 'Jumlah');
        $this->styleHeaderRow($sheet, "A{$row}:B{$row}");
        $row++;

        $statusFirstRow = $row;
        foreach ($report['byStatus'] as $status) {
            $sheet->setCellValue("A{$row}", $status['label']);
            $sheet->setCellValue("B{$row}", $status['count']);
            $row++;
        }
        $statusLastRow = $row - 1;
        $row++;

        $sheet->setCellValue("A{$row}", 'Kategori');
        $sheet->setCellValue("B{$row}", 'Selesai');
        $sheet->setCellValue("C{$row}", 'On Track');
        $sheet->setCellValue("D{$row}", 'Pending');
        $sheet->setCellValue("E{$row}", 'Total');
        $this->styleHeaderRow($sheet, "A{$row}:E{$row}");
        $row++;

        $categoryFirstRow = $row;
        foreach ($report['byCategory'] as $category) {
            $sheet->setCellValue("A{$row}", $category['label']);
            $sheet->setCellValue("E{$row}", $category['count']);
            $row++;
        }
        $categoryLastRow = $row - 1;
        $this->addDataBar($sheet, "E{$categoryFirstRow}:E{$categoryLastRow}", self::CATEGORY_COLORS['project']);

        $staffLabels = null;
        $staffValues = null;

        if ($report['byStaff']->isNotEmpty()) {
            $row++;
            $sheet->setCellValue("A{$row}", 'Staff');
            $sheet->setCellValue("B{$row}", 'Selesai');
            $sheet->setCellValue("C{$row}", 'On Track');
            $sheet->setCellValue("D{$row}", 'Pending');
            $sheet->setCellValue("E{$row}", 'Total');
            $this->styleHeaderRow($sheet, "A{$row}:E{$row}");
            $row++;

            $staffFirstRow = $row;
            foreach ($report['byStaff'] as $staff) {
                $sheet->setCellValue("A{$row}", $staff['name']);
                $sheet->setCellValue("B{$row}", $staff['byStatus']['selesai'] ?? 0);
                $sheet->setCellValue("C{$row}", $staff['byStatus']['on_track'] ?? 0);
                $sheet->setCellValue("D{$row}", $staff['byStatus']['pending'] ?? 0);
                $sheet->setCellValue("E{$row}", $staff['total']);
                $row++;
            }
            $staffLastRow = $row - 1;
            $this->addDataBar($sheet, "E{$staffFirstRow}:E{$staffLastRow}", self::HEADER_FILL_HEX);

            $staffLabels = "'Ringkasan'!\$A\${$staffFirstRow}:\$A\${$staffLastRow}";
            $staffValues = "'Ringkasan'!\$E\${$staffFirstRow}:\$E\${$staffLastRow}";
        }

        $sheet->getColumnDimension('A')->setWidth(32);
        foreach (['B', 'C', 'D', 'E'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(14);
        }

        return [
            'statusLabels' => "'Ringkasan'!\$A\${$statusFirstRow}:\$A\${$statusLastRow}",
            'statusValues' => "'Ringkasan'!\$B\${$statusFirstRow}:\$B\${$statusLastRow}",
            'categoryLabels' => "'Ringkasan'!\$A\${$categoryFirstRow}:\$A\${$categoryLastRow}",
            'categoryValues' => "'Ringkasan'!\$E\${$categoryFirstRow}:\$E\${$categoryLastRow}",
            'staffLabels' => $staffLabels,
            'staffValues' => $staffValues,
        ];
    }

    /**
     * Project progress tracker — one row per logged activity in kategori=project this period.
     *
     * @param  Report  $report
     */
    private function buildProjectsSheet(Worksheet $sheet, array $report): void
    {
        $sheet->setTitle('Proyek');

        $sheet->setCellValue('A1', 'Proyek');
        $sheet->setCellValue('B1', 'Staff');
        $sheet->setCellValue('C1', 'Progress (%)');
        $sheet->setCellValue('D1', 'Target Selesai');
        $sheet->setCellValue('E1', 'Status');
        $this->styleHeaderRow($sheet, 'A1:E1');

        $row = 2;

        foreach ($report['projects'] as $project) {
            $sheet->setCellValue("A{$row}", $project['deskripsi']);
            $sheet->setCellValue("B{$row}", $project['staff']);
            $sheet->setCellValue("C{$row}", $project['progress_percent']);
            $sheet->setCellValue("D{$row}", $project['target_selesai']);
            $sheet->setCellValue("E{$row}", $this->statusLabel($project['status']));
            $row++;
        }

        if ($row > 2) {
            $sheet->setAutoFilter('A1:E'.($row - 1));
            $this->addDataBar($sheet, 'C2:C'.($row - 1), self::CATEGORY_COLORS['project'], min: 0, max: 100);
        }

        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(45);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(14);
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
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Deskripsi');
        $this->styleHeaderRow($sheet, 'A1:E1');

        $row = 2;

        foreach ($report['detailsByCategory'] as $category) {
            foreach ($category['activities'] as $activity) {
                $sheet->setCellValue("A{$row}", $activity['tanggal']);
                $sheet->setCellValue("B{$row}", $activity['staff']);
                $sheet->setCellValue("C{$row}", $category['label']);
                $sheet->setCellValue("D{$row}", $this->statusLabel($activity['status']));
                $sheet->setCellValue("E{$row}", $activity['deskripsi']);
                $row++;
            }
        }

        if ($row > 2) {
            $sheet->setAutoFilter('A1:E'.($row - 1));
        }

        $sheet->freezePane('A2');

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(60);
    }

    /**
     * @param  array{statusLabels: string, statusValues: string, categoryLabels: string, categoryValues: string, staffLabels: ?string, staffValues: ?string}  $ranges
     */
    private function buildDashboardSheet(Worksheet $sheet, array $ranges): void
    {
        $sheet->setTitle('Dashboard');

        $sheet->setCellValue('A1', 'Dashboard — Laporan Aktivitas Mingguan');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->addChart($this->makePieChart(
            'statusChart',
            'Status Pengerjaan',
            $ranges['statusLabels'],
            $ranges['statusValues'],
            'A3',
            'H20',
            array_values(self::STATUS_COLORS),
        ));

        $sheet->addChart($this->makeBarChart(
            'categoryChart',
            'Distribusi Kategori',
            $ranges['categoryLabels'],
            $ranges['categoryValues'],
            'I3',
            'R20',
            array_map(fn (ActivityCategory $category) => self::CATEGORY_COLORS[$category->value], ActivityCategory::cases()),
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

    /**
     * @param  string[]|null  $pointColors  One hex color per data point (category/pie slice), in range order.
     */
    private function makeBarChart(
        string $name,
        string $title,
        string $labelRange,
        string $valueRange,
        string $topLeftCell,
        string $bottomRightCell,
        ?array $pointColors = null,
    ): Chart {
        return $this->makeChart(DataSeries::TYPE_BARCHART, $name, $title, $labelRange, $valueRange, $topLeftCell, $bottomRightCell, $pointColors);
    }

    /**
     * @param  string[]|null  $pointColors  One hex color per data point (category/pie slice), in range order.
     */
    private function makePieChart(
        string $name,
        string $title,
        string $labelRange,
        string $valueRange,
        string $topLeftCell,
        string $bottomRightCell,
        ?array $pointColors = null,
    ): Chart {
        return $this->makeChart(DataSeries::TYPE_PIECHART, $name, $title, $labelRange, $valueRange, $topLeftCell, $bottomRightCell, $pointColors);
    }

    /**
     * @param  string[]|null  $pointColors  One hex color per data point (category/pie slice), in range order.
     */
    private function makeChart(
        string $type,
        string $name,
        string $title,
        string $labelRange,
        string $valueRange,
        string $topLeftCell,
        string $bottomRightCell,
        ?array $pointColors = null,
    ): Chart {
        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $labelRange, null, 5)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $valueRange, null, 5)];

        if ($pointColors !== null) {
            $values[0]->setFillColor($pointColors);
        }

        $series = new DataSeries(
            $type,
            $type === DataSeries::TYPE_BARCHART ? DataSeries::GROUPING_CLUSTERED : null,
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

    /**
     * Native Excel "data bar" conditional formatting — an in-cell colored bar, the closest built-in
     * equivalent to the div-based progress bars used in the PDF export and the design mockup.
     */
    private function addDataBar(Worksheet $sheet, string $range, string $color, ?int $min = null, ?int $max = null): void
    {
        $dataBar = new ConditionalDataBar;
        $dataBar->setMinimumConditionalFormatValueObject(new ConditionalFormatValueObject($min === null ? 'min' : 'num', $min));
        $dataBar->setMaximumConditionalFormatValueObject(new ConditionalFormatValueObject($max === null ? 'max' : 'num', $max));
        $dataBar->setColor($color);

        $conditional = new Conditional;
        $conditional->setConditionType(Conditional::CONDITION_DATABAR);
        $conditional->setDataBar($dataBar);

        $sheet->getStyle($range)->setConditionalStyles([$conditional]);
    }

    private function styleHeaderRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setARGB(self::HEADER_FONT);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::HEADER_FILL);
    }

    private function statusLabel(string $value): string
    {
        return ActivityStatus::from($value)->label();
    }
}
