@props(['nombre'])

{{-- Íconos del portal: trazo de 2px sobre una cuadrícula de 24, mismo estilo en toda la página. --}}
<svg {{ $attributes->class('fx-icono') }} viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    @switch($nombre)
        @case('personas')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            @break
        @case('ubicacion')
            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
            @break
        @case('usuario')
            <circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/>
            @break
        @case('megafono')
            <path d="m3 11 18-5v12L3 14v-3Z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>
            @break
        @case('buscar')
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            @break
        @case('flecha')
            <path d="M5 12h14M12 5l7 7-7 7"/>
            @break
        @case('letrero')
            <path d="M12 3v3M12 13v8"/><path d="M18.5 13h-13L2 9.5 5.5 6h13L22 9.5Z"/>
            @break
        @case('via')
            <rect x="2" y="6" width="20" height="8" rx="1"/><path d="M17 14v7M7 14v7M17 3v3M7 3v3M10 14 2.3 6.3M14 6l7.7 7.7M8 6l8 8"/>
            @break
        @case('foco')
            <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6M10 22h4"/>
            @break
        @case('basura')
            <path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2M10 11v6M14 11v6"/>
            @break
        @case('alerta')
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/>
            @break
        @case('descargar')
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
            @break
        @case('celular')
            <rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('cerrar')
            <path d="m6 6 12 12M18 6 6 18"/>
            @break
    @endswitch
</svg>
