@extends('layouts.app')

@section('content')
@php
    $expirySummaries = $expiryGroups->keyBy(fn ($group) => $group->expiration_date->toDateString());
    $nearestExpiry = $expiryGroups->first();
    $nearestExpiryDate = $nearestExpiry?->expiration_date?->toDateString();
    $pageExpiryGroups = $batches->getCollection()->groupBy(fn ($batch) => $batch->expiration_date->toDateString());
    $storageParameters = ['blood_type' => $bloodType, 'component' => $componentKey];
@endphp

<div class="cbis-page-heading">
    <div>
        <div class="cbis-eyebrow">Current storage</div>
        <h1 class="cbis-page-title mb-0">{{ $bloodType }} · {{ $componentLabel }}</h1>
        <p class="cbis-page-subtitle">Review stored batches by expiry date.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('blood-inventory.index') }}#current-storage" class="btn btn-outline-secondary">Back to inventory</a>
        <a href="{{ route('blood-inventory.index', $storageParameters) }}#stock-batches" class="btn btn-outline-secondary">Stock records</a>
    </div>
</div>

<div class="cbis-inventory-summary-grid mb-3" data-live-region="storage-summary">
    <section class="card cbis-inventory-summary-card">
        <small>Units in storage</small>
        <strong>{{ $totalUnits }}</strong>
        <span>Non-expired units, including reserved stock</span>
    </section>
    <section class="card cbis-inventory-summary-card">
        <small>Stock batches</small>
        <strong>{{ $batchCount }}</strong>
        <span>Current stock records for this blood type and component</span>
    </section>
    <section class="card cbis-inventory-summary-card">
        <small>Nearest expiry</small>
        @if($nearestExpiryDate)
            <strong><time datetime="{{ $nearestExpiryDate }}">{{ \Illuminate\Support\Carbon::parse($nearestExpiryDate)->format('M d, Y') }}</time></strong>
            <span>{{ (int) data_get($nearestExpiry, 'units') }} unit{{ (int) data_get($nearestExpiry, 'units') === 1 ? '' : 's' }} expire on this date</span>
        @else
            <strong>—</strong>
            <span>No current stock batches</span>
        @endif
    </section>
</div>

<p class="small text-muted">One unit is one blood bag. A stock batch may contain several bags only when they share the same expiration date; bags with different dates belong in separate batches.</p>

<form method="GET" action="{{ route('blood-inventory.storage') }}" class="card card-body cbis-storage-filter mb-3" data-auto-filter="true" aria-label="Filter stored blood bags">
    <input type="hidden" name="blood_type" value="{{ $bloodType }}">
    <input type="hidden" name="component" value="{{ $componentKey }}">
    <div class="row g-2 align-items-end">
    <div class="col-md-6 col-lg-5 cbis-storage-filter-field">
        <label for="storage-expiration-date" class="form-label">Expiry date</label>
        <select id="storage-expiration-date" name="expiration_date" class="form-select">
            <option value="">All expiry dates</option>
            @foreach($expirySummaries as $expiryDate => $expirySummary)
                <option value="{{ $expiryDate }}" @selected($selectedExpiry === $expiryDate)>{{ \Illuminate\Support\Carbon::parse($expiryDate)->format('M d, Y') }} · {{ (int) data_get($expirySummary, 'units') }} units · {{ (int) data_get($expirySummary, 'batch_count') }} batch{{ (int) data_get($expirySummary, 'batch_count') === 1 ? '' : 'es' }}</option>
            @endforeach
            @if($selectedExpiry && ! $expirySummaries->has($selectedExpiry))
                <option value="{{ $selectedExpiry }}" selected>{{ \Illuminate\Support\Carbon::parse($selectedExpiry)->format('M d, Y') }} · no stored units</option>
            @endif
        </select>
    </div>
    <div class="col-md-6 col-lg-4 cbis-storage-filter-field">
        <label for="storage-sort" class="form-label">Stock order</label>
        <select id="storage-sort" name="sort" class="form-select">
            <option value="oldest" @selected($sort === 'oldest')>Oldest expiry first</option>
            <option value="latest" @selected($sort === 'latest')>Latest expiry first</option>
        </select>
    </div>
    <div class="col-md-12 col-lg-3 cbis-storage-filter-actions">
        <button class="btn btn-danger js-auto-filter-submit">Filter</button>
        <a href="{{ route('blood-inventory.storage', $storageParameters) }}" class="btn btn-outline-secondary">All expiry dates</a>
    </div>
    </div>
</form>

<div data-live-region="storage-batches">
    @if($batches->count())
        <p class="small text-muted cbis-storage-results-note">Showing {{ $batches->firstItem() }}–{{ $batches->lastItem() }} of {{ $batches->total() }} stock batch{{ $batches->total() === 1 ? '' : 'es' }}. Displaying {{ $filteredUnits }} individual blood bag{{ $filteredUnits === 1 ? '' : 's' }}.</p>
        @foreach($pageExpiryGroups as $expiryDate => $dateBatches)
            @php
                $expirySummary = $expirySummaries->get($expiryDate);
                $dateUnits = (int) data_get($expirySummary, 'units');
                $dateBatchCount = (int) data_get($expirySummary, 'batch_count');
            @endphp
            <section class="cbis-storage-expiry-group" data-expiration-date="{{ $expiryDate }}" aria-labelledby="storage-expiry-{{ $expiryDate }}">
                <div class="cbis-storage-expiry-heading">
                    <h2 class="h6 mb-0" id="storage-expiry-{{ $expiryDate }}">Expires <time datetime="{{ $expiryDate }}">{{ \Illuminate\Support\Carbon::parse($expiryDate)->format('M d, Y') }}</time></h2>
                    <p>{{ $dateUnits }} unit{{ $dateUnits === 1 ? '' : 's' }} · {{ $dateBatchCount }} stock batch{{ $dateBatchCount === 1 ? '' : 'es' }} total</p>
                </div>
                <div class="cbis-storage-batch-grid" aria-label="Blood bags expiring {{ $expiryDate }}">
                    @foreach($dateBatches as $batch)
                        @php
                            $units = (int) $batch->units_available;
                            $displayStatus = $batch->status === 'expired' || $batch->expiration_date?->lt(today()) ? 'Expired' : ($units === 0 ? 'Depleted' : ($units <= 5 ? 'Low stock' : 'In storage'));
                            $statusClass = in_array($displayStatus, ['Expired', 'Depleted']) ? 'cbis-status-expired' : ($displayStatus === 'Low stock' ? 'cbis-status-low' : 'cbis-status-active');
                        @endphp
                        @for($unitNumber = 1; $unitNumber <= $units; $unitNumber++)
                            <article class="card cbis-storage-batch-card cbis-storage-unit-card" data-stock-record-id="{{ $batch->id }}" data-unit-number="{{ $unitNumber }}">
                                <div class="card-body">
                                    <div class="cbis-storage-batch-header">
                                        <h3 class="cbis-eyebrow mb-0">Blood bag #{{ $unitNumber }}</h3>
                                        <span class="badge {{ $statusClass }}">{{ $displayStatus }}</span>
                                    </div>
                                    <strong class="cbis-storage-batch-units">1 <small>unit</small></strong>
                                    <dl class="cbis-storage-batch-details">
                                        <div><dt>Expires</dt><dd><time datetime="{{ $expiryDate }}">{{ $batch->expiration_date->format('M d, Y') }}</time></dd></div>
                                        <div><dt>Source</dt><dd class="cbis-storage-batch-source">{{ $batch->donationRecord ? 'Donation '.$batch->donationRecord->donation_no : 'Manual stock entry' }}</dd></div>
                                    </dl>
                                </div>
                            </article>
                        @endfor
                    @endforeach
                </div>
            </section>
        @endforeach
    @elseif($batches->total() > 0)
        <div class="card">
            <div class="cbis-empty-state">
                <strong>No batches on this page</strong>
                <span>Matching stock is still available. Return to the first page to view the current batches.</span>
                <a href="{{ route('blood-inventory.storage', $storageParameters + ($selectedExpiry ? ['expiration_date' => $selectedExpiry] : [])) }}" class="btn btn-outline-secondary mt-2">Return to first page</a>
            </div>
        </div>
    @else
        <div class="card">
            <div class="cbis-empty-state">
                <strong>{{ $selectedExpiry ? 'No stored batches for this expiry date' : 'No current stock batches' }}</strong>
                <span>{{ $selectedExpiry ? 'Choose another expiry date or view all current batches for this blood type and component.' : 'Non-expired batches with available units will appear here.' }}</span>
                <a href="{{ $selectedExpiry ? route('blood-inventory.storage', $storageParameters) : route('blood-inventory.index') . '#current-storage' }}" class="btn btn-outline-secondary mt-2">{{ $selectedExpiry ? 'View all expiry dates' : 'Back to inventory' }}</a>
            </div>
        </div>
    @endif
</div>

<div class="mt-3" data-live-region="storage-pages">{{ $batches->links() }}</div>
@endsection

@push('scripts')
<script src="{{ asset('js/cbis-storage-filter.js') }}?v={{ filemtime(public_path('js/cbis-storage-filter.js')) }}"></script>
@endpush
