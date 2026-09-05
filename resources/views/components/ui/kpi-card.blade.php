@props([
    'label' => '',
    'value' => '',
    'statusClass' => '',
    'suffix' => null,
    'icon' => null,
])
@php
$icon ??= match (true) {
    str_contains(strtolower($label), 'donor') => 'users',
    str_contains(strtolower($label), 'stock'), str_contains(strtolower($label), 'blood units') => 'drop',
    str_contains(strtolower($label), 'activit'), str_contains(strtolower($label), 'approv') => 'calendar',
    str_contains(strtolower($label), 'request'), str_contains(strtolower($label), 'reservation') => 'report',
    str_contains(strtolower($label), 'release') => 'arrow',
    default => 'grid',
};
@endphp

<div class="cbis-kpi h-100">
    <div class="card-body">
        <span class="cbis-kpi-icon"><x-ui.icon :name="$icon" /></span>
        <div class="label mb-2">{{ $label }}</div>
        <div class="value {{ $statusClass }}">{{ $value }}</div>
        @if($suffix)
            <div class="small text-muted mt-1">{{ $suffix }}</div>
        @endif
    </div>
</div>
