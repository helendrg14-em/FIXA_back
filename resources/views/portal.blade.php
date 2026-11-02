<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FIXA: reporta baches, focos apagados y basura en Santa Cruz</title>
    <meta name="description" content="Con FIXA reportas un bache, una falla de alumbrado o un microbasural en Santa Cruz de la Sierra y sigues su avance con un código, sin crear cuenta.">
    <link rel="icon" href="{{ asset('images/fixa-marca.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/fixa-fuentes.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fixa-portal.css') }}">
</head>
<body>
<a class="saltar" href="#contenido">Saltar al contenido</a>

<header class="portada">
    <nav class="barra" aria-label="Principal">
        <a href="{{ route('portal') }}" class="marca" aria-label="FIXA, inicio">
            <img src="{{ asset('images/fixa-logo-claro.svg') }}" alt="" width="118" height="40">
        </a>
        <a class="barra-enlace" href="#descarga">Descargar la app</a>
    </nav>

    <div class="portada-cuerpo">
        <div class="portada-texto">
            <h1>Lo reportas una vez. Sabes qué pasó con tu reclamo.</h1>
            <p>Un bache, un foco apagado o basura acumulada en Santa Cruz: toma una foto, marca el lugar y sigue el avance con tu propio código.</p>
            <a class="boton boton-claro" href="#descarga">Quiero reportar</a>
        </div>

        {{-- Esquema de los anillos de Santa Cruz: identidad visual, no un mapa de casos reales. --}}
        <figure class="anillos" aria-hidden="true">
            <svg viewBox="0 0 800 800" focusable="false">
                <g class="anillos-radios">
                    <path d="M400 400 400 0M400 400 683 117M400 400 800 400M400 400 683 683M400 400 400 800M400 400 117 683M400 400 0 400M400 400 117 117"/>
                </g>
                <g class="anillos-vias">
                    <circle cx="400" cy="400" r="62"/>
                    <circle cx="400" cy="400" r="122"/>
                    <circle cx="400" cy="400" r="190"/>
                    <circle cx="400" cy="400" r="268"/>
                    <circle cx="400" cy="400" r="356"/>
                </g>
                <rect class="anillos-plaza" x="386" y="386" width="28" height="28"/>
                <g class="punto" transform="translate(534 266)">
                    <circle r="33"/>
                    <path d="M-15 8c5-8 9-9 13-5s8 5 17-3" />
                    <path d="M-15-5h8m6 0h8m6 0h2" />
                </g>
                <g class="punto" transform="translate(212 330)">
                    <circle r="33"/>
                    <path d="M0-18a11 11 0 0 1 7 19.500V6H-7V1.500A11 11 0 0 1 0-18zM-5 12H5M-3 17H3"/>
                </g>
                <g class="punto" transform="translate(452 604)">
                    <circle r="33"/>
                    <path d="M-13-10H13M-9-10-7 16H7L9-10M-4-10V-15H4V-10M-3-4-2 10M3-4 2 10"/>
                </g>
            </svg>
        </figure>
    </div>
</header>

<main id="contenido">
    <section class="reportes" aria-labelledby="que-reportar">
        <h2 id="que-reportar">Qué puedes reportar</h2>
        <dl class="lista-reportes">
            <div>
                <dt>Baches y deterioro vial</dt>
                <dd>Lo atiende la Secretaría Municipal de Obras Públicas.</dd>
            </div>
            <div>
                <dt>Fallas de alumbrado público</dt>
                <dd>Lo atiende la UOD de Alumbrado Público.</dd>
            </div>
            <div>
                <dt>Microbasurales y residuos</dt>
                <dd>Lo atiende EMACRUZ.</dd>
            </div>
        </dl>
    </section>

    <section class="recorrido" aria-labelledby="que-pasa">
        <h2 id="que-pasa">Qué pasa después de reportar</h2>
        <ol>
            <li>
                <h3>Envías tu reporte</h3>
                <p>Con foto, descripción y ubicación en el mapa. No necesitas crear una cuenta.</p>
            </li>
            <li>
                <h3>Recibes un código y una clave</h3>
                <p>Con ellos consultas el avance cuando quieras. Guárdalos: son solo tuyos.</p>
            </li>
            <li>
                <h3>La unidad responsable lo atiende</h3>
                <p>El supervisor define la prioridad según el riesgo y asigna una cuadrilla.</p>
            </li>
            <li>
                <h3>Se cierra con evidencia</h3>
                <p>El supervisor aprueba la foto del trabajo. Si no estás de acuerdo, tienes 15 días para pedir una revisión.</p>
            </li>
        </ol>
    </section>

    <section class="otros" aria-labelledby="otro-problema">
        <h2 id="otro-problema">Si tu problema es otro</h2>
        <p class="otros-intro">FIXA solo recibe las tres categorías de arriba. Para estos casos, acude a:</p>
        <ul>
            <li><strong>CRE</strong> <span>Cortes de luz en tu domicilio y fallas de la red eléctrica.</span></li>
            <li><strong>ABC</strong> <span>Carreteras de la red vial fundamental.</span></li>
            <li><strong>Gobernación de Santa Cruz</strong> <span>Asuntos de competencia departamental.</span></li>
        </ul>
        <p class="otros-aviso">FIXA no atiende emergencias. Si hay peligro inmediato, llama a los servicios de emergencia.</p>
    </section>

    <section class="descarga" id="descarga" aria-labelledby="titulo-descarga">
        <h2 id="titulo-descarga">Reporta desde tu celular</h2>
        @if (filled(config('fixa.app_url')))
            <p>La app funciona en celulares Android.</p>
            <a class="boton boton-oscuro" href="{{ config('fixa.app_url') }}">Descargar la app para Android</a>
        @else
            <p>La app para Android estará disponible pronto en esta página.</p>
            <span class="boton boton-inactivo" role="link" aria-disabled="true">Descargar la app para Android</span>
        @endif
    </section>
</main>

<footer class="pie">
    <p>FIXA es un proyecto académico de la Universidad Privada del Valle, Santa Cruz. No es un canal oficial del Gobierno Autónomo Municipal.</p>
    <a href="{{ url('/admin') }}">Acceso de funcionarios</a>
</footer>
</body>
</html>
