<?php

namespace Tests\Feature;

use App\Exports\SelectedReportsExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportExportFormattingTest extends TestCase
{
    public function test_excel_report_has_readable_columns_and_requester_signature_area(): void
    {
        $export = new SelectedReportsExport([[
            'title' => 'Inventory',
            'summary' => ['Records' => 1, 'Total units' => 3],
            'headings' => ['Record', 'Blood type', 'Component', 'Units', 'Expiry', 'Status'],
            'rows' => [['1', 'O+', 'Packed Red Blood Cells', '3', '2026-10-15', 'Active']],
        ]], 'both', 'September 2026', 'Mark Erezuela', 'QAO User', 'Sep 29, 2026 9:00 AM');

        $path = tempnam(sys_get_temp_dir(), 'cbis-report-');
        try {
            file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows = $sheet->toArray();
            $firstColumn = array_column($rows, 0);

            $this->assertSame('Bacolod Main Chapter Reports', $sheet->getCell('A1')->getValue());
            $this->assertContains('Mark Erezuela', $firstColumn);
            $this->assertContains('Signature of requester', $firstColumn);
            $this->assertContains('Printed by QAO User on Sep 29, 2026 9:00 AM', $firstColumn);
            $this->assertGreaterThan(20, $sheet->getColumnDimension('C')->getWidth());
            $this->assertSame(1, $sheet->getPageSetup()->getFitToWidth());
        } finally {
            unlink($path);
        }
    }
}
