@props([
    'label' => '',
    'value' => '',
    'statusClass' => '',
    'suffix' => null,
])

<div class="cbis-kpi h-100">
    <div class="card-body">
        <div class="label mb-2">{{ $label }}</div>
        <div class="value {{ $statusClass }}">{{ $value }}</div>
        @if($suffix)
            <div class="small text-muted mt-1">{{ $suffix }}</div>
        @endif
    </div>
</div>
