<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centralized Blood Inventory System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/cbis-ui.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @livewireStyles
</head>
<body class="bg-light">
@php
    $webAuthenticated = auth('web')->check();
    $donorAuthenticated = auth('donor')->check();
    $webUser = $webAuthenticated ? auth('web')->user() : null;
    $isCentralAdmin = $webAuthenticated && $webUser?->isCentralAdmin();
    $isFacilityAdmin = $webAuthenticated && $webUser?->hasRole('Facility Admin / Blood Bank Personnel');
    $isMedicalTechnologist = $webAuthenticated && $webUser?->hasRole('Medical Technologist');

    // Mirror route middleware access checks so navbar only shows allowed modules.
    $canManageDonors = $webAuthenticated && ($isCentralAdmin || $isFacilityAdmin || $webUser?->can('manage donors'));
    $canManageDonations = $webAuthenticated && ($isCentralAdmin || $isFacilityAdmin || $webUser?->can('manage donation records'));
    $canManageBloodletting = $webAuthenticated && ($isCentralAdmin || $isMedicalTechnologist || $webUser?->can('manage bloodletting records'));
    $canManageInventory = $webAuthenticated && ($isCentralAdmin || $isFacilityAdmin || $webUser?->can('manage inventory'));
    $canManageReleases = $webAuthenticated && ($isCentralAdmin || $isFacilityAdmin || $isMedicalTechnologist || $webUser?->can('manage blood releases'));
    $canManageSchedules = $webAuthenticated && ($isCentralAdmin || $isFacilityAdmin || $webUser?->can('manage schedules'));
    $canManageLocations = $webAuthenticated && ($isCentralAdmin || $webUser?->can('manage locations'));
    $canViewReports = $webAuthenticated && ($isCentralAdmin || $isFacilityAdmin || $webUser?->can('view reports'));

    $showNotificationCenter = $webAuthenticated && ($webUser?->isCentralAdmin() || $webUser?->hasRole('Facility Admin / Blood Bank Personnel'));
    $lowStockType = \App\Notifications\LowStockAlert::class;
    $unreadCount = $showNotificationCenter ? $webUser->unreadNotifications()->where('type', $lowStockType)->count() : 0;
    $recentNotifications = $showNotificationCenter ? $webUser->notifications()->where('type', $lowStockType)->latest()->limit(5)->get() : collect();
@endphp
<nav class="navbar navbar-expand-lg navbar-dark cbis-navbar">
    <div class="container">
        <a class="navbar-brand" href="{{ $webAuthenticated ? route('dashboard') : ($donorAuthenticated ? route('donor.portal.profile') : route('public.index')) }}">CBIS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @if($webAuthenticated)
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Home</a></li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.index') }}">Public Portal</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('facility-application.create') }}">Apply Facility</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.map') }}">Events & Map</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.availability') }}">Available Bloods</a></li>
                @endif
            </ul>
            <div class="d-flex">
                @if($donorAuthenticated)
                    <a href="{{ route('donor.portal.profile') }}" class="btn btn-outline-light btn-sm me-2">Donor Portal</a>
                    <a href="{{ route('password.change') }}" class="btn btn-outline-light btn-sm me-2">Change Password</a>
                    <form method="POST" action="{{ route('logout') }}" class="js-logout-form">
                        @csrf
                        <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
                    </form>
                @elseif($webAuthenticated)
                    @php
                        $roleLabel = $webUser?->getRoleNames()->first() ?? 'Staff User';
                    @endphp
                    <div class="cbis-user-meta me-3" title="{{ $webUser?->name }} ({{ $roleLabel }})">
                        <div class="cbis-user-name">{{ $webUser?->name }}</div>
                        <div class="cbis-user-role">{{ $roleLabel }}</div>
                    </div>
                    @if($showNotificationCenter)
                        <div class="dropdown me-2">
                            <button class="btn btn-outline-light btn-sm position-relative cbis-bell-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                                <span aria-hidden="true">&#128276;</span>
                                @if($unreadCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                        {{ $unreadCount }}
                                    </span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 360px;">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <strong>Low Stock Alerts</strong>
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button class="btn btn-link btn-sm text-decoration-none p-0">Mark all read</button>
                                    </form>
                                </div>
                                <div class="list-group list-group-flush">
                                    @forelse($recentNotifications as $notification)
                                        @php
                                            $data = $notification->data ?? [];
                                        @endphp
                                        <div class="list-group-item small">
                                            <div class="fw-semibold">{{ $data['title'] ?? 'Notification' }}</div>
                                            <div>Facility: {{ $data['facility_name'] ?? 'N/A' }}</div>
                                            <div>Blood Type: {{ $data['blood_type'] ?? 'N/A' }} | Units: {{ $data['units_available'] ?? 'N/A' }}</div>
                                            <div class="text-muted mb-1">{{ $notification->created_at?->diffForHumans() }}</div>
                                            @if($notification->read_at === null)
                                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-outline-secondary">Mark as read</button>
                                                </form>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="list-group-item text-muted small">No alerts yet.</div>
                                    @endforelse
                                </div>
                                <div class="border-top p-2 text-end">
                                    <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-danger">View all notifications</a>
                                </div>
                            </div>
                        </div>
                    @endif
                    <a href="{{ route('password.change') }}" class="btn btn-outline-light btn-sm me-2">Change Password</a>
                    <form method="POST" action="{{ route('logout') }}" class="js-logout-form">
                        @csrf
                        <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Login</a>
                @endif
            </div>
        </div>
    </div>
</nav>
@if($webAuthenticated)
    @include('partials.section-tabs')
@endif
<main class="container cbis-main py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.querySelectorAll('.js-logout-form').forEach((form) => {
    form.addEventListener('submit', () => {
        // Inform other tabs to return to the unified login page.
        localStorage.setItem('cbis_logout', String(Date.now()));
    });
});

window.addEventListener('storage', (event) => {
    if (event.key === 'cbis_logout') {
        window.location.href = "{{ route('login') }}";
    }
});
</script>
@livewireScripts
@stack('scripts')
</body>
</html>
