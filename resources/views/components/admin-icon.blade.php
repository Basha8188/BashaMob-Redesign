@props(['name' => 'dashboard'])
@php
    $paths = [
        'dashboard' => 'M4 4h6v6H4V4Zm10 0h6v9h-6V4ZM4 14h6v6H4v-6Zm10 3h6v3h-6v-3Z',
        'store' => 'M4 10v10h16V10M3 10l2-6h14l2 6M8 20v-6h5v6M3 10c0 2 4 2 4 0 0 2 4 2 4 0 0 2 4 2 4 0 0 2 4 2 4 0',
        'products' => 'm4 7 8-4 8 4-8 4-8-4Zm0 0v10l8 4 8-4V7m-8 4v10',
        'sales' => 'M5 4h14v16H5V4Zm3 4h8m-8 4h5m-5 4h7M16 2v4M8 2v4',
        'purchases' => 'M4 5h2l2 10h9l2-7H7m2 10a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm8 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z',
        'inventory' => 'M3 7h18v13H3V7Zm3-3h12v3H6V4Zm3 7h6',
        'repairs' => 'm14.5 6.5 3-3a5 5 0 0 1-6 6l-7 7a1.5 1.5 0 1 0 2 2l7-7a5 5 0 0 1 6-6l-3 3-2-2Z',
        'customers' => 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm9 10v-2a4 4 0 0 0-3-3.87M16 2.13a4 4 0 0 1 0 7.75',
        'suppliers' => 'M3 7h11v9H3V7Zm11 3h4l3 3v3h-7v-6ZM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        'expenses' => 'M4 6h16v13H4V6Zm0 4h16M8 15h4M7 3h10v3',
        'reports' => 'M4 20V10m5 10V4m6 16v-7m5 7V7',
        'team' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0m2-8v6m-3-3h6',
        'settings' => 'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm8-3.5 2-1-2-3-2 .3-1.5-1.5.3-2L14 3l-1 2h-2l-1-2-3 1.8.3 2L5.8 8.3 4 8l-2 3 2 1v2l-2 1 2 3 1.8-.3 1.5 1.5-.3 2L10 23l1-2h2l1 2 3-1.8-.3-2 1.5-1.5 1.8.3 2-3-2-1v-2Z',
    ];
@endphp
<span {{ $attributes->class('admin-icon') }}><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['dashboard'] }}"/></svg></span>
