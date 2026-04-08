<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

function writeSheet($sheet, $title, $rows) {
    $sheet->setTitle($title);
    $r = 1;
    foreach ($rows as $row) {
        $c = 1;
        foreach ($row as $value) {
            $sheet->setCellValueByColumnAndRow($c, $r, $value);
            $c++;
        }
        $r++;
    }
    $lastCol = $sheet->getHighestColumn();
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// UNF
$sheet = $spreadsheet->getActiveSheet();
writeSheet($sheet, 'UNF', [
    ['record_id', 'facility_name', 'donor_names', 'donor_ids', 'course_or_event_codes', 'event_titles', 'blood_types', 'statuses'],
    [1, 'NorthLand Blood Bank', 'Chris P Bacon, Cholo Mollica', 'D-001, D-002', 'EV-001, EV-001', 'Community Donation Drive, Community Donation Drive', 'A+, O+', 'planned, planned'],
    [2, 'City Blood Center', 'Mike Hawk', 'D-003', 'EV-002', 'Bloodletting Activity', 'B+', 'ongoing'],
]);

// 1NF
$sheet1 = $spreadsheet->createSheet();
writeSheet($sheet1, '1NF', [
    ['record_id', 'facility_name', 'donor_id', 'donor_name', 'event_code', 'event_title', 'event_type', 'blood_type', 'status'],
    [1, 'NorthLand Blood Bank', 'D-001', 'Chris P Bacon', 'EV-001', 'Community Donation Drive', 'blood_donation', 'A+', 'planned'],
    [2, 'NorthLand Blood Bank', 'D-002', 'Cholo Mollica', 'EV-001', 'Community Donation Drive', 'blood_donation', 'O+', 'planned'],
    [3, 'City Blood Center', 'D-003', 'Mike Hawk', 'EV-002', 'Bloodletting Activity', 'bloodletting', 'B+', 'ongoing'],
]);

// 2NF
$sheet2 = $spreadsheet->createSheet();
writeSheet($sheet2, '2NF', [
    ['TABLE', 'COLUMN 1', 'COLUMN 2', 'COLUMN 3', 'COLUMN 4', 'COLUMN 5', 'COLUMN 6', 'COLUMN 7'],
    ['facilities', 'facility_id (PK)', 'code', 'name', 'type', 'contact_person', 'contact_number', 'address'],
    ['users', 'user_id (PK)', 'facility_id (FK)', 'name', 'email', 'phone', 'password', 'is_active'],
    ['donors', 'donor_id (PK)', 'facility_id (FK)', 'first_name', 'last_name', 'blood_type', 'contact_number', 'is_eligible'],
    ['donation_records', 'donation_id (PK)', 'facility_id (FK)', 'donor_id (FK)', 'donation_no', 'donated_at', 'volume_ml', 'status'],
    ['blood_inventory', 'inventory_id (PK)', 'facility_id (FK)', 'donation_id (FK)', 'blood_type', 'units_available', 'expiration_date', 'status'],
    ['blood_releases', 'release_id (PK)', 'facility_id (FK)', 'inventory_id (FK)', 'released_at', 'units_released', 'purpose', 'released_by'],
    ['donation_schedules', 'event_id (PK)', 'facility_id (FK)', 'title', 'event_type', 'event_date', 'start_time', 'status'],
]);

// 3NF
$sheet3 = $spreadsheet->createSheet();
writeSheet($sheet3, '3NF', [
    ['FINAL 3NF TABLES (CBIS)', 'KEYS / NOTES'],
    ['facilities', 'PK: id'],
    ['users', 'PK: id, FK: facility_id -> facilities.id'],
    ['donors', 'PK: id, FK: facility_id -> facilities.id'],
    ['donation_records', 'PK: id, FK: facility_id, donor_id, recorded_by'],
    ['bloodletting_records', 'PK: id, FK: facility_id, donation_record_id, medical_technologist_id'],
    ['blood_inventory', 'PK: id, FK: facility_id, donation_record_id'],
    ['blood_releases', 'PK: id, FK: facility_id, blood_inventory_id, released_by'],
    ['donation_schedules (events)', 'PK: id, FK: facility_id'],
    ['blood_bank_locations', 'PK: id, FK: facility_id'],
    ['facility_applications', 'PK: id, FK: reviewed_by, facility_id'],
    ['audit_logs', 'PK: id, FK: facility_id, user_id'],
    ['notifications', 'PK: id (uuid), morph: notifiable_type/notifiable_id'],
    ['roles, permissions, pivots', 'RBAC normalization (Spatie)'],
]);

$dir = __DIR__ . '/docs';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$file = $dir . '/cbis-normalization-1nf-3nf.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($file);

echo "Created: {$file}" . PHP_EOL;
