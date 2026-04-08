<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

function addRows($sheet, &$r, $rows) {
    foreach ($rows as $row) {
        $c = 1;
        foreach ($row as $value) {
            $sheet->setCellValueByColumnAndRow($c, $r, $value);
            $c++;
        }
        $r++;
    }
}

function autoSize($sheet, $maxCol = 'L') {
    foreach (range('A', $maxCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// Sheet 1: UNF
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('UNF');
$r = 1;
addRows($sheet, $r, [[
    'record_id','facility_name','facility_code','staff_name','donor_names','donor_ids','event_titles','event_types','event_dates','blood_types','inventory_units','statuses'
],[
    1,'NorthLand Blood Bank','FAC-006','NorthLand Admin','Chris P Bacon, Cholo Mollica','D-001, D-002','Community Donation Drive, Community Donation Drive','blood_donation, blood_donation','2026-04-05, 2026-04-05','A+, O+','3, 2','planned, planned'
],[
    2,'City Blood Center','FAC-001','Facility Admin','Mike Hawk','D-003','Bloodletting Activity','bloodletting','2026-04-10','B+','1','ongoing'
]]);
autoSize($sheet, 'L');

// Sheet 2: 1NF
$sheet1 = $spreadsheet->createSheet();
$sheet1->setTitle('1NF');
$r = 1;
addRows($sheet1, $r, [[
    'row_id','facility_code','facility_name','staff_name','donor_id','donor_name','event_title','event_type','event_date','blood_type','inventory_units','status'
],[1,'FAC-006','NorthLand Blood Bank','NorthLand Admin','D-001','Chris P Bacon','Community Donation Drive','blood_donation','2026-04-05','A+',3,'planned'],
[2,'FAC-006','NorthLand Blood Bank','NorthLand Admin','D-002','Cholo Mollica','Community Donation Drive','blood_donation','2026-04-05','O+',2,'planned'],
[3,'FAC-001','City Blood Center','Facility Admin','D-003','Mike Hawk','Bloodletting Activity','bloodletting','2026-04-10','B+',1,'ongoing']
]);
autoSize($sheet1, 'L');

// Sheet 3: 2NF
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('2NF');
$r = 1;
addRows($sheet2, $r, [['Facilities Table']]);
$r++;
addRows($sheet2, $r, [['facility_id','code','name','type','contact_person','contact_number','is_active'],
[1,'FAC-001','City Blood Center','blood_bank','Facility Admin','+639170000000',1],
[6,'FAC-006','NorthLand Blood Bank','blood_bank','NorthLand Contact','+639171234567',1]]);
$r++;
addRows($sheet2, $r, [['Donors Table']]);
$r++;
addRows($sheet2, $r, [['donor_id','facility_id','first_name','last_name','blood_type','contact_number'],
['D-001',6,'Chris P','Bacon','A+','+639181111111'],
['D-002',6,'Cholo','Mollica','O+','+639182222222'],
['D-003',1,'Mike','Hawk','B+','+639183333333']]);
$r++;
addRows($sheet2, $r, [['Events (Donation Schedules) Table']]);
$r++;
addRows($sheet2, $r, [['event_id','facility_id','title','event_type','event_date','status'],
['EV-001',6,'Community Donation Drive','blood_donation','2026-04-05','planned'],
['EV-002',1,'Bloodletting Activity','bloodletting','2026-04-10','ongoing']]);
$r++;
addRows($sheet2, $r, [['Transaction Table (atomic reference rows)']]);
$r++;
addRows($sheet2, $r, [['txn_id','facility_id','donor_id','event_id','blood_type','units','status'],
[1,6,'D-001','EV-001','A+',3,'planned'],
[2,6,'D-002','EV-001','O+',2,'planned'],
[3,1,'D-003','EV-002','B+',1,'ongoing']]);
autoSize($sheet2, 'L');

// Sheet 4: 3NF (actual CBIS tables)
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('3NF');
$r = 1;
addRows($sheet3, $r, [['CBIS 3NF Final Schema (Actual)']]);
$r++;
addRows($sheet3, $r, [['Table','Primary Key','Main Foreign Keys','Notes']]);
addRows($sheet3, $r, [
['facilities','id','-','Master facility records'],
['users','id','facility_id -> facilities.id','Staff accounts per facility / central'],
['donors','id','facility_id -> facilities.id','Donor master data'],
['donation_records','id','facility_id, donor_id, recorded_by','Donation transactions'],
['bloodletting_records','id','facility_id, donation_record_id, medical_technologist_id','Verification records'],
['blood_inventory','id','facility_id, donation_record_id','Real-time stock table'],
['blood_releases','id','facility_id, blood_inventory_id, released_by','Usage/release logs'],
['donation_schedules','id','facility_id','Events (donation/bloodletting)'],
['blood_bank_locations','id','facility_id','Map coordinates per facility'],
['facility_applications','id','reviewed_by, facility_id','Public join request + review'],
['audit_logs','id','facility_id, user_id','Critical action logs'],
['notifications','id (uuid)','morph notifiable_type/notifiable_id','System notifications'],
['roles','id','-','RBAC role definitions'],
['permissions','id','-','RBAC permissions'],
['role_has_permissions','(permission_id,role_id)','permission_id, role_id','RBAC pivot'],
['model_has_roles','(role_id,model_id,model_type)','role_id','RBAC pivot'],
['model_has_permissions','(permission_id,model_id,model_type)','permission_id','RBAC pivot']
]);
autoSize($sheet3, 'L');

$path = __DIR__ . '/docs/cbis-normalization-1nf-3nf-detailed.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($path);

echo "Created: {$path}" . PHP_EOL;
