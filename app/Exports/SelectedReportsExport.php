<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class SelectedReportsExport extends StringValueBinder implements FromArray, WithCustomValueBinder
{
    public function __construct(private array $sections) {}

    public function array(): array
    {
        $rows = [['Bacolod Main Chapter Reports']];
        foreach ($this->sections as $section) {
            $rows[] = [$section['title']];
            if ($section['summary'] !== null) {
                foreach ($section['summary'] as $label => $value) {
                    $rows[] = [$label, (string) $value];
                }
            }
            $rows[] = $section['headings'];
            array_push($rows, ...$section['rows']);
            $rows[] = [];
        }

        return $rows;
    }
}
