<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #17233b; font-size: 12px; margin: 42px; }
        h1 { color: #b70732; font-size: 21px; margin-bottom: 5px; }
        .sub { color: #59657a; margin-bottom: 28px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 11px 13px; border-bottom: 1px solid #dce1e9; text-align: left; vertical-align: top; }
        th { width: 34%; color: #59657a; font-weight: normal; }
        .notice { margin-top: 36px; padding: 15px; background: #f6f1f2; color: #7b1630; line-height: 1.5; }
    </style>
</head>
<body>
    <h1>{{ $type === 'blood_request' ? 'Blood Request Form' : 'Patient Profile Record' }}</h1>
    <div class="sub">Reference {{ $reservation->reference }} &middot; {{ $reservation->facility->name }}</div>
    <table>
        <tr><th>Patient</th><td>{{ $reservation->patient->name }}</td></tr>
        <tr><th>Request reference</th><td>{{ $reservation->reference }}</td></tr>
        @if($type === 'blood_request')
            <tr><th>Blood needed</th><td>{{ $reservation->units_requested }} unit(s) of {{ $reservation->blood_type }} {{ \App\Models\BloodInventory::COMPONENTS[$reservation->component] }}</td></tr>
            <tr><th>Needed on</th><td>{{ $reservation->needed_on->format('F j, Y') }}</td></tr>
            <tr><th>Purpose</th><td>{{ $reservation->clinical_purpose ?: 'Hospital transfusion request' }}</td></tr>
        @else
            <tr><th>Date of birth</th><td>{{ $reservation->patient->birth_date?->format('F j, Y') ?: 'Not recorded' }}</td></tr>
            <tr><th>Contact number</th><td>{{ $reservation->patient->phone ?: 'Not recorded' }}</td></tr>
            <tr><th>Address</th><td>{{ $reservation->patient->address ?: 'Not recorded' }}</td></tr>
        @endif
    </table>
    <div class="notice">Fictional record for local presentation. It is not a medical order or a valid identification document.</div>
</body>
</html>
