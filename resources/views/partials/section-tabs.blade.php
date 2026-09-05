@php
    $user = auth('web')->user();
    $isCentral = $user?->isCentralAdmin();
    $canViewNotifications = $user?->isQao() || $user?->isEventFacilitator() || $user?->isBloodBankStaff();

    $groups = [
        'Overview' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => '⌂', 'show' => true],
            ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => '◌', 'show' => $canViewNotifications],
        ],
        'Blood Operations' => [
            ['label' => 'Donor Records', 'route' => 'donors.index', 'icon' => '♙', 'show' => $user?->can('manage donors') || $user?->can('view limited donors') || $user?->can('view detailed donors')],
            ['label' => 'Donations', 'route' => 'donation-records.index', 'icon' => '✚', 'show' => $user?->can('manage donation records')],
            ['label' => 'Bloodletting', 'route' => 'bloodletting-records.index', 'icon' => '◉', 'show' => $user?->can('manage bloodletting records')],
            ['label' => 'Inventory', 'route' => 'blood-inventory.index', 'icon' => '▣', 'show' => $user?->can('view inventory') || $user?->can('manage inventory')],
            ['label' => 'Releases', 'route' => 'blood-releases.index', 'icon' => '◇', 'show' => $user?->can('view blood releases') || $user?->can('manage blood releases')],
            ['label' => 'Reservations', 'route' => 'reservations.index', 'icon' => '▤', 'show' => $user?->can('process reservations') || $user?->can('monitor reservations')],
        ],
        'Activities & Reports' => [
            ['label' => 'Events & Activities', 'route' => 'donation-schedules.index', 'icon' => '□', 'show' => $user?->can('review activities') || $user?->can('manage schedules')],
            ['label' => 'Locations', 'route' => 'blood-bank-locations.index', 'icon' => '⌖', 'show' => $user?->can('manage locations')],
            ['label' => $user?->can('export reports') ? 'Reports & Exports' : 'System Summary', 'route' => 'reports.index', 'icon' => '▥', 'show' => $user?->can('view reports') || $user?->can('request summaries')],
        ],
        'Administration' => [
            ['label' => 'Facilities', 'route' => 'facilities.index', 'icon' => '▦', 'show' => $isCentral],
            ['label' => 'Staff Management', 'route' => 'staff-users.index', 'icon' => '♧', 'show' => $user?->can('manage users')],
        ],
    ];
@endphp

<aside class="cbis-staff-sidebar" aria-label="Section navigation">
    <div class="cbis-sidebar-role">
        <strong>{{ $user?->isQao() ? 'Quality Assurance' : ($user?->isBloodBankStaff() ? 'Blood Bank Staff' : 'Event Facilitator') }}</strong>
        <span>{{ $user?->facility?->name ?? 'Bacolod Main Chapter' }}</span>
    </div>
    @foreach($groups as $group => $tabs)
        @if(collect($tabs)->contains(fn ($tab) => $tab['show'] ?? false))
            <div class="cbis-nav-group">
                <div class="cbis-nav-group-label">{{ $group }}</div>
                <ul class="nav flex-column">
                @foreach($tabs as $tab)
                @if($tab['show'] ?? false)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs(str_replace('.index', '.*', $tab['route'])) || request()->routeIs($tab['route']) ? 'active' : '' }}"
                           href="{{ route($tab['route']) }}">
                            <span aria-hidden="true">{{ $tab['icon'] }}</span>{{ $tab['label'] }}
                        </a>
                    </li>
                @endif
                @endforeach
                </ul>
            </div>
        @endif
    @endforeach
</aside>
