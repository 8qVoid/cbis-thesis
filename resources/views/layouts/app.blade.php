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
<a href="#main-content" class="cbis-skip-link">Skip to content</a>
@php
    $webAuthenticated = auth('web')->check();
    $donorAuthenticated = auth('donor')->check();
    $webUser = $webAuthenticated ? auth('web')->user() : null;
    $isCentralAdmin = $webAuthenticated && $webUser?->isCentralAdmin();

    $lowStockType = \App\Notifications\LowStockAlert::class;
    $facilityApplicationType = \App\Notifications\FacilityApplicationSubmitted::class;
    $notificationTypes = [];
    $notificationTitle = 'Notifications';

    if ($webAuthenticated && $webUser?->isCentralAdmin()) {
        $notificationTypes = [$facilityApplicationType, $lowStockType];
        $notificationTitle = 'System Alerts';
    } elseif ($webAuthenticated && ($webUser?->hasRole('Facilitator') || $webUser?->can('manage inventory'))) {
        $notificationTypes = [$lowStockType];
        $notificationTitle = 'Low Stock Alerts';
    }

    $showNotificationCenter = $webAuthenticated && $notificationTypes !== [];
    $unreadCount = 0;
    $recentNotifications = collect();

    if ($showNotificationCenter) {
        $unreadQuery = $webUser->unreadNotifications()->whereIn('type', $notificationTypes);
        $recentQuery = $webUser->notifications()->whereIn('type', $notificationTypes);

        if (! $webUser->isCentralAdmin()) {
            $unreadQuery->where('data->facility_id', $webUser->facility_id);
            $recentQuery->where('data->facility_id', $webUser->facility_id);
        }

        $unreadCount = $unreadQuery->count();
        $recentNotifications = $recentQuery->latest()->limit(5)->get();
    }
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
                    @if(! $donorAuthenticated)
                        <li class="nav-item"><a class="nav-link" href="{{ route('public.index') }}">Public Portal</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('facility-application.create') }}">Apply Facility</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.map') }}">Events & Map</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.availability') }}">Available Bloods</a></li>
                @endif
            </ul>
            <div class="d-flex align-items-center cbis-nav-actions">
                @if($donorAuthenticated)
                    <a href="{{ route('donor.portal.profile') }}" class="btn btn-outline-light btn-sm me-2">Profile</a>
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
                                <svg class="cbis-bell-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M18 8.2a6 6 0 0 0-12 0v3.1c0 .7-.24 1.38-.68 1.92L4 14.8V17h16v-2.2l-1.32-1.58A2.98 2.98 0 0 1 18 11.3V8.2Z" />
                                    <path d="M9.75 19a2.25 2.25 0 0 0 4.5 0" />
                                </svg>
                                @if($unreadCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                        {{ $unreadCount }}
                                    </span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0 cbis-notification-menu">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <strong>{{ $notificationTitle }}</strong>
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button class="btn btn-link btn-sm text-decoration-none p-0">Mark all read</button>
                                    </form>
                                </div>
                                @if($webUser?->isCentralAdmin())
                                    <div class="d-flex gap-2 px-3 py-2 border-bottom">
                                        <a href="{{ route('notifications.index', ['type' => 'facility_application']) }}" class="btn btn-sm btn-outline-danger flex-fill">Applications</a>
                                        <a href="{{ route('notifications.index', ['type' => 'low_stock']) }}" class="btn btn-sm btn-outline-danger flex-fill">Low stock</a>
                                    </div>
                                @endif
                                <div class="list-group list-group-flush">
                                    @forelse($recentNotifications as $notification)
                                        @php
                                            $data = $notification->data ?? [];
                                        @endphp
                                        <div class="list-group-item small">
                                            <div class="fw-semibold">{{ $data['title'] ?? 'Notification' }}</div>
                                            @if($notification->type === $facilityApplicationType)
                                                <div>Organization: {{ $data['organization_name'] ?? 'N/A' }}</div>
                                                <div>Contact: {{ $data['contact_person'] ?? 'N/A' }}</div>
                                            @else
                                                <div>Facility: {{ $data['facility_name'] ?? 'N/A' }}</div>
                                                <div>Blood Type: {{ $data['blood_type'] ?? 'N/A' }} | Units: {{ $data['units_available'] ?? 'N/A' }}</div>
                                            @endif
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
<main id="main-content" class="container cbis-main py-4">
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
<div class="modal fade" id="cbisConfirmModal" tabindex="-1" aria-labelledby="cbisConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cbis-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <p class="text-danger small fw-semibold mb-1">Confirm action</p>
                    <h2 class="modal-title h4" id="cbisConfirmTitle">Are you sure?</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-muted" id="cbisConfirmMessage">Please confirm this action.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="cbisConfirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let cbisPendingConfirmForm = null;
const cbisConfirmModalElement = document.getElementById('cbisConfirmModal');
const cbisConfirmModal = cbisConfirmModalElement ? new bootstrap.Modal(cbisConfirmModalElement) : null;
const cbisConfirmTitle = document.getElementById('cbisConfirmTitle');
const cbisConfirmMessage = document.getElementById('cbisConfirmMessage');
const cbisConfirmButton = document.getElementById('cbisConfirmButton');

document.querySelectorAll('.js-confirm-action').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === 'true' || !cbisConfirmModal) {
            return;
        }

        event.preventDefault();
        cbisPendingConfirmForm = form;

        cbisConfirmTitle.textContent = form.dataset.confirmTitle || 'Confirm action?';
        cbisConfirmMessage.textContent = form.dataset.confirmMessage || 'Please confirm this action.';
        cbisConfirmButton.textContent = form.dataset.confirmButton || 'Confirm';
        cbisConfirmButton.className = `btn btn-${form.dataset.confirmVariant || 'danger'}`;
        cbisConfirmModal.show();
    });
});

cbisConfirmButton?.addEventListener('click', () => {
    if (!cbisPendingConfirmForm) {
        return;
    }

    cbisPendingConfirmForm.dataset.confirmed = 'true';
    cbisConfirmButton.disabled = true;
    cbisConfirmButton.textContent = 'Working...';
    cbisPendingConfirmForm.submit();
});

cbisConfirmModalElement?.addEventListener('hidden.bs.modal', () => {
    cbisPendingConfirmForm = null;
    cbisConfirmButton.disabled = false;
});

document.querySelectorAll('.js-logout-form').forEach((form) => {
    form.addEventListener('submit', () => {
        // Inform other tabs to return to the unified login page.
        localStorage.setItem('cbis_logout', String(Date.now()));
    });
});

document.querySelectorAll('.js-mobile-suffix').forEach((input) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/\D/g, '').slice(0, 9);
    });
});

document.querySelectorAll('.js-person-name').forEach((input) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/\d/g, '').slice(0, 80);
    });
});

document.querySelectorAll('.js-contact-numbers').forEach((input) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9()+,\-\s]/g, '').slice(0, Number(input.maxLength) || 60);
    });
});

document.querySelectorAll('form[data-auto-filter="true"]').forEach((form) => {
    let submitTimer = null;

    const scheduleSubmit = () => {
        window.clearTimeout(submitTimer);
        submitTimer = window.setTimeout(() => {
            if (form.dataset.autoSubmitting === 'true') {
                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            form.dataset.autoSubmitting = 'true';
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }, 450);
    };

    form.querySelectorAll('select, input').forEach((field) => {
        if (field.matches('[type="hidden"], [type="submit"], [type="button"], [data-auto-filter-ignore]')) {
            return;
        }

        field.addEventListener('change', scheduleSubmit);

        if (field.matches('[type="date"], [type="month"]')) {
            field.addEventListener('input', () => {
                if (field.validity.valid) {
                    scheduleSubmit();
                }
            });
        }
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
