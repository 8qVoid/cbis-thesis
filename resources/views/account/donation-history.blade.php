@extends('layouts.app')
@section('content')
<div class="cbis-page-heading">
    <div>
        <h1 class="cbis-page-title mb-0">My Donation History</h1>
        <p class="cbis-page-subtitle">Donations recorded for your account by Blood Bank Staff.</p>
    </div>
    <a href="{{ route('account.dashboard', ['view' => 'donor']) }}" class="btn btn-outline-secondary">Back to Donor View</a>
</div>

<div class="card cbis-record-table" data-live-region="donor-donation-history">
    <div class="table-responsive cbis-mobile-table-wrap">
        <table class="table cbis-mobile-card-table mb-0">
            <caption class="caption-top px-3 py-2 small text-muted">{{ $donations->total() }} recorded donation{{ $donations->total() === 1 ? '' : 's' }}. Staff add a record after collection.</caption>
            <thead><tr><th scope="col">Donation</th><th scope="col">Date</th><th scope="col">Facility</th><th scope="col">Blood</th><th scope="col">Status</th><th scope="col">Bloodletting</th></tr></thead>
            <tbody>
                @forelse($donations as $record)
                    <tr class="cbis-reservation-row">
                        <td data-label="Donation"><strong>{{ $record->donation_no }}</strong></td>
                        <td data-label="Date">{{ $record->donated_at?->format('M j, Y · g:i A') ?? 'Not recorded' }}</td>
                        <td data-label="Facility">{{ $record->facility?->name ?? 'Not assigned' }}</td>
                        <td data-label="Blood">{{ $record->blood_type }} · {{ $record->volume_ml }} ml</td>
                        <td data-label="Status">{{ str($record->status)->headline() }}</td>
                        <td data-label="Bloodletting">{{ $record->bloodlettingRecord ? str($record->bloodlettingRecord->verification_status)->headline() : 'Not recorded' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="cbis-empty-state"><strong>No donations recorded yet</strong><span>When Blood Bank Staff record your donation, it will appear here.</span><a href="{{ route('public.map') }}" class="btn btn-sm btn-outline-danger mt-2">Find a donation event</a></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $donations->links() }}</div>
@endsection
