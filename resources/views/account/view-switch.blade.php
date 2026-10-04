<nav class="cbis-segments" aria-label="Dashboard view">
    @foreach(['both' => 'Patient/Donor', 'donor' => 'Donor only', 'patient' => 'Patient only'] as $view => $label)
        @if(isset($availableViews[$view]))
            <a href="{{ route('account.dashboard', ['view' => $view]) }}" class="{{ $selectedView === $view ? 'active' : '' }}" @if($selectedView === $view) aria-current="page" @endif>{{ $label }}</a>
        @endif
    @endforeach
</nav>
