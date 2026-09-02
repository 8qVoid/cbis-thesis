@php
    $user = auth('web')->user();
    $isCentral = $user?->isCentralAdmin();
    $canViewNotifications = $user?->isQao() || $user?->isEventFacilitator() || $user?->isBloodBankStaff();

    $tabs = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'show' => true],
        ['label' => 'Records', 'route' => 'donors.index', 'show' => $user?->can('manage donors') || $user?->can('view limited donors') || $user?->can('view detailed donors')],
        ['label' => 'Donations', 'route' => 'donation-records.index', 'show' => $user?->can('manage donation records')],
        ['label' => 'Bloodletting', 'route' => 'bloodletting-records.index', 'show' => $user?->can('manage bloodletting records')],
        ['label' => 'Inventory', 'route' => 'blood-inventory.index', 'show' => $user?->can('view inventory') || $user?->can('manage inventory')],
        ['label' => 'Releases', 'route' => 'blood-releases.index', 'show' => $user?->can('view blood releases') || $user?->can('manage blood releases')],
        ['label' => 'Reservations', 'route' => 'reservations.index', 'show' => $user?->can('process reservations') || $user?->can('monitor reservations')],
        ['label' => 'Events', 'route' => 'donation-schedules.index', 'show' => $user?->can('review activities') || $user?->can('manage schedules')],
        ['label' => 'Locations', 'route' => 'blood-bank-locations.index', 'show' => $user?->can('manage locations')],
        ['label' => 'Reports', 'route' => 'reports.index', 'show' => $user?->can('view reports') || $user?->can('request summaries')],
        ['label' => 'Notifications', 'route' => 'notifications.index', 'show' => $canViewNotifications],
        ['label' => 'Facilities', 'route' => 'facilities.index', 'show' => $isCentral],
        ['label' => 'Staff', 'route' => 'staff-users.index', 'show' => $user?->can('manage users')],
    ];
@endphp

<div class="cbis-section-tabs py-2 mb-4" aria-label="Section navigation">
    <div class="container cbis-main">
        <ul class="nav nav-pills gap-2 flex-nowrap cbis-tab-strip">
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
