<!doctype html>
<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px}table{width:100%;border-collapse:collapse;margin-bottom:20px}th,td{border:1px solid #ccc;padding:5px;text-align:left}th{background:#eee}h2{color:#9d1730}thead{display:table-header-group}tr{page-break-inside:avoid}</style></head>
<body><h1>Bacolod Main Chapter Reports</h1><p>{{ $periodLabel }}</p>
@foreach($sections as $section)
<h2>{{ $section['title'] }}</h2>
@if($section['summary'] !== null) @foreach($section['summary'] as $label=>$value)<p>{{ $label }}: {{ $value }}</p>@endforeach @endif
@if($detail !== 'summary')<table><thead><tr>@foreach($section['headings'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>@forelse($section['rows'] as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($section['headings']) }}">No records in this period.</td></tr>@endforelse</tbody></table>@endif
@endforeach</body></html>
