@php
    $user = auth('web')->user();
    $isCentral = $user?->isCentralAdmin();
    $canViewNotifications = $user?->isQao() || $user?->isEventFacilitator() || $user?->isBloodBankStaff();

    $groups = [
        'Overview' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'show' => true],
            ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'bell', 'show' => $canViewNotifications],
        ],
        'Blood Operations' => [
            ['label' => 'Donor Records', 'route' => 'donors.index', 'icon' => 'users', 'show' => $user?->can('manage donors') || $user?->can('view limited donors') || $user?->can('view detailed donors')],
            ['label' => 'Donations', 'route' => 'donation-records.index', 'icon' => 'drop', 'show' => $user?->can('manage donation records')],
            ['label' => 'Bloodletting', 'route' => 'bloodletting-records.index', 'icon' => 'drop', 'show' => $user?->can('manage bloodletting records')],
            ['label' => 'Inventory', 'route' => 'blood-inventory.index', 'icon' => 'grid', 'show' => $user?->can('view inventory') || $user?->can('manage inventory')],
            ['label' => 'Releases', 'route' => 'blood-releases.index', 'icon' => 'arrow', 'show' => $user?->can('view blood releases') || $user?->can('manage blood releases')],
            ['label' => 'Reservations', 'route' => 'reservations.index', 'icon' => 'report', 'show' => $user?->can('process reservations') || $user?->can('monitor reservations')],
        ],
        'Activities & Reports' => [
            ['label' => 'Events & Activities', 'route' => 'donation-schedules.index', 'icon' => 'calendar', 'show' => $user?->can('review activities') || $user?->can('manage schedules')],
            ['label' => 'Locations', 'route' => 'blood-bank-locations.index', 'icon' => 'pin', 'show' => $user?->can('manage locations')],
            ['label' => $user?->can('export reports') ? 'Reports & Exports' : 'System Summary', 'route' => 'reports.index', 'icon' => 'report', 'show' => $user?->can('view reports') || $user?->can('request summaries')],
        ],
        'Administration' => [
            ['label' => 'Facilities', 'route' => 'facilities.index', 'icon' => 'grid', 'show' => $isCentral],
            ['label' => 'Staff Management', 'route' => 'staff-users.index', 'icon' => 'users', 'show' => $user?->can('manage users')],
        ],
    ];
@endphp

<button class="btn cbis-section-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#staffSections" aria-controls="staffSections" aria-expanded="false"><x-ui.icon name="grid" /> Sections <span aria-hidden="true">⌄</span></button>
<aside id="staffSections" class="cbis-staff-sidebar collapse d-md-block" aria-label="Section navigation">
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
                            <x-ui.icon :name="$tab['icon']" />{{ $tab['label'] }}
                        </a>
                    </li>
                @endif
                @endforeach
                </ul>
            </div>
        @endif
    @endforeach
</aside>
