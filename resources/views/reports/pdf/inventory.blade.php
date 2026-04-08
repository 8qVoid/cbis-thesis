<!doctype html><html><head><meta charset="utf-8"><style>body{font-family: DejaVu Sans, sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #111;padding:6px}</style></head><body>
<h3>Blood Inventory Report</h3>
<table><thead><tr><th>Blood Type</th><th>Units</th><th>Expiration Date</th><th>Status</th></tr></thead><tbody>
@foreach($records as $record)
<tr><td>{{ $record->blood_type }}</td><td>{{ $record->units_available }}</td><td>{{ $record->expiration_date?->toDateString() }}</td><td>{{ $record->status }}</td></tr>
@endforeach
</tbody></table>
</body></html>
