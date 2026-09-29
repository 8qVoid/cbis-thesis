<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class SelectedReportsExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithEvents
{
    private ?array $layout = null;

    public function __construct(
        private array $sections,
        private string $detail,
        private string $periodLabel,
        private string $requestedBy,
        private string $printedBy,
        private string $printedAt,
    ) {}

    public function array(): array
    {
        return $this->layout()['rows'];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $layout = $this->layout();
            $lastColumn = Coordinate::stringFromColumnIndex($layout['columnCount']);
            $lastRow = count($layout['rows']);

            $sheet->setShowGridlines(false);
            $sheet->freezePane('A4');
            $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getFont()->setName('Aptos')->setSize(10);
            $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

            foreach ($layout['widths'] as $index => $width) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
            }

            $sheet->mergeCells("A1:{$lastColumn}1");
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '9D1730']],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(32);
            $sheet->mergeCells("A2:{$lastColumn}2");
            $sheet->getStyle("A2:{$lastColumn}2")->getFont()->getColor()->setRGB('667085');
            $sheet->getRowDimension(2)->setRowHeight(24);

            foreach ($layout['sectionRows'] as $row) {
                $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '9D1730']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE8ED']],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(25);
            }

            foreach ($layout['headingRows'] as $row) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '344054']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F4F7']],
                    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D5DD']]],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(24);
            }

            foreach ($layout['dataRanges'] as [$first, $last]) {
                $sheet->getStyle("A{$first}:{$lastColumn}{$last}")->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_HAIR);
            }

            $signature = $layout['signature'];
            foreach (['label', 'name', 'line', 'caption', 'meta'] as $part) {
                $row = $signature[$part];
                $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            }
            $sheet->getStyle("A{$signature['label']}:{$lastColumn}{$signature['label']}")->getFont()->setBold(true);
            $sheet->getStyle("A{$signature['name']}:{$lastColumn}{$signature['name']}")->getFont()->setBold(true)->setSize(12);
            $sheet->getRowDimension($signature['name'])->setRowHeight(30);
            $sheet->getRowDimension($signature['line'])->setRowHeight(30);
            $sheet->getStyle("A{$signature['line']}:C{$signature['line']}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$signature['meta']}:{$lastColumn}{$signature['meta']}")->getFont()->getColor()->setRGB('667085');

            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                ->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setLeft(0.3)->setRight(0.3)->setTop(0.5)->setBottom(0.5);
            $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");
        }];
    }

    private function layout(): array
    {
        if ($this->layout !== null) {
            return $this->layout;
        }

        $rows = [['Bacolod Main Chapter Reports'], [$this->periodLabel], []];
        $columnCount = max(3, ...array_map(fn ($section) => count($section['headings']), $this->sections));
        $widths = array_fill(0, $columnCount, 14);
        $sectionRows = $headingRows = $dataRanges = [];

        foreach ($this->sections as $section) {
            $sectionRows[] = count($rows) + 1;
            $rows[] = [$section['title']];
            if ($section['summary'] !== null) {
                foreach ($section['summary'] as $label => $value) {
                    $rows[] = [$label, (string) $value];
                    $widths[0] = max($widths[0], mb_strlen($label) + 3);
                }
            }

            if ($this->detail !== 'summary') {
                $headingRows[] = count($rows) + 1;
                $rows[] = $section['headings'];
                $firstDataRow = count($rows) + 1;
                foreach ($section['rows'] as $record) {
                    $rows[] = $record;
                }
                if ($section['rows'] === []) {
                    $rows[] = ['No records in this period.'];
                }
                $dataRanges[] = [$firstDataRow, count($rows)];

                foreach ([$section['headings'], ...$section['rows']] as $record) {
                    foreach ($record as $index => $value) {
                        $widths[$index] = max($widths[$index], min(44, mb_strlen((string) $value) + 3));
                    }
                }
            }
            $rows[] = [];
        }

        $rows[] = [];
        $signature = [];
        foreach (['label' => 'Requested by', 'name' => $this->requestedBy, 'line' => '',
            'caption' => 'Signature of requester', 'meta' => "Printed by {$this->printedBy} on {$this->printedAt}"] as $part => $value) {
            $signature[$part] = count($rows) + 1;
            $rows[] = [$value];
        }

        return $this->layout = compact('rows', 'columnCount', 'widths', 'sectionRows', 'headingRows', 'dataRanges', 'signature');
    }
}
