<div class="d-flex flex-wrap gap-2 mb-3 cbis-public-nav">
    <a href="{{ route('public.index') }}" class="btn {{ request()->routeIs('public.index') ? 'btn-danger' : 'btn-outline-danger' }}">Public Portal</a>
    <a href="{{ route('public.map') }}" class="btn {{ request()->routeIs('public.map') ? 'btn-danger' : 'btn-outline-danger' }}">Events & Map</a>
    <a href="{{ route('public.availability') }}" class="btn {{ request()->routeIs('public.availability') ? 'btn-danger' : 'btn-outline-danger' }}">Available Bloods</a>
</div>
