@props(['name', 'size' => 20])

<svg
    {{ $attributes->class(['shrink-0'])->merge([
        'width' => $size,
        'height' => $size,
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.8',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
    ]) }}
>
    @switch($name)
        @case('documents')
            <path d="M6 3.75h9.25L19 7.5v12.75H6z" />
            <path d="M15 3.75V7.5h4M9 11h7M9 15h7" />
            @break
        @case('file-plus')
            <path d="M6 3.75h9.25L19 7.5v12.75H6z" />
            <path d="M15 3.75V7.5h4M12.5 11v6M9.5 14h6" />
            @break
        @case('layers')
            <path d="m12 3 9 5-9 5-9-5z" />
            <path d="m3 12 9 5 9-5M3 16l9 5 9-5" />
            @break
        @case('shield-check')
            <path d="M12 3 5 6v5c0 4.6 2.8 8.1 7 10 4.2-1.9 7-5.4 7-10V6z" />
            <path d="m8.7 12 2.1 2.1 4.6-4.6" />
            @break
        @case('log-out')
            <path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10" />
            @break
        @case('panel-close')
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M9 4v16m7-5-3-3 3-3" />
            @break
        @case('panel-open')
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M9 4v16m4 11 3 3-3 3" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('arrow-left')
            <path d="m15 18-6-6 6-6" />
            @break
        @case('arrow-right')
            <path d="m9 18 6-6-6-6" />
            @break
        @case('edit')
            <path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z" />
            @break
        @case('download')
            <path d="M12 3v12m-4-4 4 4 4-4M5 20h14" />
            @break
        @case('file-text')
            <path d="M6 3.75h9.25L19 7.5v12.75H6z" />
            <path d="M15 3.75V7.5h4M9 11h7M9 14h7M9 17h4" />
            @break
        @case('qr')
            <rect x="3" y="3" width="6" height="6" rx="1" />
            <rect x="15" y="3" width="6" height="6" rx="1" />
            <rect x="3" y="15" width="6" height="6" rx="1" />
            <path d="M15 15h2v2h-2zM19 15h2v5h-2M15 19h2v2h-2" />
            @break
        @case('send')
            <path d="m21 3-7.5 18-3.1-7.4L3 10.5zM10.4 13.6 21 3" />
            @break
        @case('save')
            <path d="M5 4h12l2 2v14H5zM8 4v6h8V4M8 20v-6h8v6" />
            @break
        @case('x')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.7 2.7L16.5 9" />
            @break
        @case('alert-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v6m0 4h.01" />
            @break
        @case('info')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v6m0-10h.01" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18" />
            @break
        @case('building')
            <path d="M4 21V7l8-4 8 4v14M8 21v-4h8v4M8 9h.01M12 9h.01M16 9h.01M8 13h.01M12 13h.01M16 13h.01" />
            @break
        @case('tag')
            <path d="M20 13 13 20 4 11V4h7z" />
            <path d="M8.5 8.5h.01" />
            @break
        @case('eye')
            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6" />
            <circle cx="12" cy="12" r="2.5" />
            @break
        @case('eye-off')
            <path d="m3 3 18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c6 0 9.5 6 9.5 6a15 15 0 0 1-2.1 2.8M6.2 6.2C3.8 7.9 2.5 12 2.5 12S6 18 12 18a9 9 0 0 0 3-.5M9.9 9.9a3 3 0 0 0 4.2 4.2" />
            @break
        @case('lock')
            <rect x="4" y="10" width="16" height="11" rx="2" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" />
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="m4 7 8 6 8-6" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-4-4" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="4" />
            <path d="M4.5 21a7.5 7.5 0 0 1 15 0" />
            @break
        @case('history')
            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
            <path d="M3 3v5h5M12 7v5l3 2" />
            @break
        @case('map-pin')
            <path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11Z" />
            <circle cx="12" cy="10" r="2.5" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
