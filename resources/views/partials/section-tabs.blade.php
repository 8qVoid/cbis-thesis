@php
    $user = auth('web')->user();
    $isCentral = $user?->isCentralAdmin();
    $isFacility = $user?->hasRole('Facility Admin / Blood Bank Personnel');

    $tabs = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'show' => true],
        ['label' => 'Records', 'route' => 'donors.index', 'show' => $isCentral || $isFacility || $user?->can('manage donors')],
        ['label' => 'Donations', 'route' => 'donation-records.index', 'show' => $isCentral || $isFacility || $user?->can('manage donation records')],
        ['label' => 'Bloodletting', 'route' => 'bloodletting-records.index', 'show' => $isCentral || $isFacility || $user?->can('manage bloodletting records')],
        ['label' => 'Inventory', 'route' => 'blood-inventory.index', 'show' => $isCentral || $isFacility || $user?->can('manage inventory')],
        ['label' => 'Releases', 'route' => 'blood-releases.index', 'show' => $isCentral || $isFacility || $user?->can('manage blood releases')],
        ['label' => 'Events', 'route' => 'donation-schedules.index', 'show' => $isCentral || $isFacility || $user?->can('manage schedules')],
        ['label' => 'Locations', 'route' => 'blood-bank-locations.index', 'show' => $isCentral || $user?->can('manage locations')],
        ['label' => 'Reports', 'route' => 'reports.index', 'show' => $isCentral || $isFacility || $user?->can('view reports')],
        ['label' => 'Notifications', 'route' => 'notifications.index', 'show' => $isCentral || $isFacility],
        ['label' => 'Facilities', 'route' => 'facilities.index', 'show' => $isCentral],
        ['label' => 'Staff', 'route' => 'staff-users.index', 'show' => $isCentral || $isFacility || $user?->can('manage users')],
        ['label' => 'Applications', 'route' => 'facility-applications.index', 'show' => $isCentral],
    ];
@endphp

<div class="cbis-section-tabs py-2 mb-4">
    <div class="container cbis-main">
        <ul class="nav nav-pills gap-2 flex-wrap">
            @foreach($tabs as $tab)
                @if($tab['show'] ?? false)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs(str_replace('.index', '.*', $tab['route'])) || request()->routeIs($tab['route']) ? 'active' : '' }}"
                           href="{{ route($tab['route']) }}">
                            {{ $tab['label'] }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</div>
