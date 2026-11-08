{{-- Las fuentes se piden junto con el HTML, sin esperar a leer fixa-fuentes.css.
     La URL debe ser la misma que usa ese CSS (sin ?v=) para que el navegador reutilice la descarga. --}}
<link rel="preload" href="{{ asset('fonts/fixa/atkinson-hyperlegible-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/fixa/atkinson-hyperlegible-latin-700-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/fixa/bricolage-grotesque-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
