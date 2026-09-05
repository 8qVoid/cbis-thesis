@props(['name' => 'grid'])
@php
$paths = [
 'home' => 'm3 10 9-7 9 7v10H3Z M9 20v-7h6v7',
 'bell' => 'M6 16V9a6 6 0 0 1 12 0v7l2 2H4Z M10 21h4',
 'users' => 'M16 21v-3a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v3 M10 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M17 4a4 4 0 0 1 0 8 M20 21v-3a4 4 0 0 0-3-4',
 'drop' => 'M12 3s-7 8-7 12a7 7 0 0 0 14 0c0-4-7-12-7-12Z',
 'calendar' => 'M4 5h16v16H4Z M8 3v4 M16 3v4 M4 10h16 M8 14h2 M14 14h2',
 'pin' => 'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z M12 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6',
 'report' => 'M5 3h10l4 4v14H5Z M9 16v-3 M12 16V9 M15 16v-5',
 'grid' => 'M3 3h7v7H3Z M14 3h7v7h-7Z M3 14h7v7H3Z M14 14h7v7h-7Z',
 'arrow' => 'M4 12h16 M14 6l6 6-6 6',
];
@endphp
<svg {{ $attributes->class(['cbis-icon']) }} viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="{{ $paths[$name] ?? $paths['grid'] }}" /></svg>
