<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Normalization');

function setCell($sheet, $cell, $value, $bold = false) {
    $sheet->setCellValue($cell, $value);
    if ($bold) {
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}

function writeTable($sheet, $startCol, $startRow, $headers, $rows, $title = null) {
    $col = $startCol;
    $row = $startRow;

    if ($title !== null) {
        setCell($sheet, $startCol.$row, $title, true);
        $row += 1;
    }

    // headers
    $c = $col;
    foreach ($headers as $h) {
        setCell($sheet, $c.$row, $h, true);
        $c++;
    }

    // data
    $r = $row + 1;
    foreach ($rows as $dataRow) {
        $c = $col;
        foreach ($dataRow as $value) {
            setCell($sheet, $c.$r, $value);
            $c++;
        }
        $r++;
    }

    // borders
    $endCol = chr(ord($startCol) + count($headers) - 1);
    $endRow = max($row, $r - 1);
    $sheet->getStyle($startCol.$row.':'.$endCol.$endRow)
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);

    $sheet->getStyle($startCol.$row.':'.$endCol.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    return $endRow + 2;
}

// UNF section
setCell($sheet, 'A1', 'UNF', true);
$unfHeaders = ['record_id','facility_name','donor_names','donor_ids','event_title','event_type','event_date','blood_types','statuses'];
$unfRows = [
    [1,'NorthLand Blood Bank','Chris P Bacon, Cholo Mollica','D-001, D-002','Community Donation Drive, Community Donation Drive','blood_donation, blood_donation','2026-04-05, 2026-04-05','A+, O+','planned, planned'],
    [2,'City Blood Center','Mike Hawk','D-003','Bloodletting Activity','bloodletting','2026-04-10','B+','ongoing'],
];
$nextRow = writeTable($sheet, 'A', 2, $unfHeaders, $unfRows);

// 1NF section
setCell($sheet, 'A'.$nextRow, '1NF', true);
$oneHeaders = ['row_id','facility_name','donor_id','donor_name','event_title','event_type','event_date','blood_type','status'];
$oneRows = [
    [1,'NorthLand Blood Bank','D-001','Chris P Bacon','Community Donation Drive','blood_donation','2026-04-05','A+','planned'],
    [2,'NorthLand Blood Bank','D-002','Cholo Mollica','Community Donation Drive','blood_donation','2026-04-05','O+','planned'],
    [3,'City Blood Center','D-003','Mike Hawk','Bloodletting Activity','bloodletting','2026-04-10','B+','ongoing'],
];
$nextRow = writeTable($sheet, 'A', $nextRow + 1, $oneHeaders, $oneRows);

// 2NF section
setCell($sheet, 'A'.$nextRow, '2NF', true);
$base2 = $nextRow + 1;

writeTable(
    $sheet,
    'A',
    $base2,
    ['facility_id','facility_name'],
    [
        [1,'City Blood Center'],
        [6,'NorthLand Blood Bank'],
    ],
    'Facilities'
);

writeTable(
    $sheet,
    'D',
    $base2,
    ['donor_id','donor_name','facility_id'],
    [
        ['D-001','Chris P Bacon',6],
        ['D-002','Cholo Mollica',6],
        ['D-003','Mike Hawk',1],
    ],
    'Donors'
);

$eventsEnd = writeTable(
    $sheet,
    'H',
    $base2,
    ['event_id','event_title','event_type'],
    [
        ['EV-001','Community Donation Drive','blood_donation'],
        ['EV-002','Bloodletting Activity','bloodletting'],
    ],
    'Events'
);

$enrollStart = max($base2 + 9, $eventsEnd + 1);
$nextRow = writeTable(
    $sheet,
    'A',
    $enrollStart,
    ['entry_id','donor_id','event_id','event_date','blood_type','status'],
    [
        [1,'D-001','EV-001','2026-04-05','A+','planned'],
        [2,'D-002','EV-001','2026-04-05','O+','planned'],
        [3,'D-003','EV-002','2026-04-10','B+','ongoing'],
    ],
    'Event Entries'
);

// 3NF section
setCell($sheet, 'A'.$nextRow, '3NF', true);
$base3 = $nextRow + 1;

writeTable(
    $sheet,
    'A',
    $base3,
    ['facility_id','code','name','type'],
    [
        [1,'FAC-001','City Blood Center','blood_bank'],
        [6,'FAC-006','NorthLand Blood Bank','blood_bank'],
    ],
    'Facilities Table'
);

writeTable(
    $sheet,
    'F',
    $base3,
    ['donor_id','facility_id','first_name','last_name','blood_type'],
    [
        ['D-001',6,'Chris P','Bacon','A+'],
        ['D-002',6,'Cholo','Mollica','O+'],
        ['D-003',1,'Mike','Hawk','B+'],
    ],
    'Donors Table'
);

$events3End = writeTable(
    $sheet,
    'L',
    $base3,
    ['event_id','facility_id','title','event_type'],
    [
        ['EV-001',6,'Community Donation Drive','blood_donation'],
        ['EV-002',1,'Bloodletting Activity','bloodletting'],
    ],
    'Events Table'
);

writeTable(
    $sheet,
    'A',
    max($base3 + 10, $events3End + 1),
    ['entry_id','donor_id','event_id','event_date','blood_type','status'],
    [
        [1,'D-001','EV-001','2026-04-05','A+','planned'],
        [2,'D-002','EV-001','2026-04-05','O+','planned'],
        [3,'D-003','EV-002','2026-04-10','B+','ongoing'],
    ],
    'Event Entries Table'
);

foreach (range('A', 'Q') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$path = __DIR__ . '/docs/cbis-normalization-like-sample.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($path);

echo "Created: {$path}" . PHP_EOL;
