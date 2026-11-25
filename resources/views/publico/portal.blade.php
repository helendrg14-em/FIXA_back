@extends('layouts.publico')

@section('titulo', 'FIXA: reporta baches, focos apagados y basura en Santa Cruz')
@section('descripcion', 'Con FIXA reportas un bache, una falla de alumbrado o un microbasural en Santa Cruz de la Sierra y sigues su avance con un código, sin crear cuenta.')

@section('contenido')
<a class="saltar" href="#contenido">Saltar al contenido</a>

<div class="franja">
    <p class="franja-portal"><x-fixa.icono nombre="personas" /> <strong>Portal ciudadano de servicios urbanos</strong></p>
    <p class="franja-lema">Reporta incidencias, sigue tu reclamo y conoce cómo se atiende tu ciudad.</p>
    <p class="franja-lugar"><x-fixa.icono nombre="ubicacion" /> Santa Cruz de la Sierra</p>
</div>

<header class="barra">
    <a href="{{ route('portal') }}" class="marca" aria-label="FIXA, inicio">
        <img src="{{ recurso('images/fixa-logo.webp') }}" alt="" width="144" height="40">
    </a>
    <button class="barra-menu" type="button" aria-expanded="false" aria-controls="barra-enlaces" aria-label="Abrir menú">
        <x-fixa.icono nombre="menu" class="abrir" />
        <x-fixa.icono nombre="cerrar" class="cerrar" />
    </button>
    <nav class="barra-enlaces" id="barra-enlaces" aria-label="Principal">
        <a class="barra-enlace" href="{{ route('portal') }}" aria-current="page">Inicio</a>
        <a class="barra-enlace" href="#reportes">Qué reportar</a>
        <a class="barra-enlace" href="#que-pasa">Cómo funciona</a>
        <a class="barra-enlace" href="#otros">Otros casos</a>
        <a class="boton boton-verde boton-chico" href="{{ url('/admin') }}">
            <x-fixa.icono nombre="usuario" /> Iniciar sesión
        </a>
    </nav>
</header>

<main id="contenido">
    <section class="portada" aria-labelledby="titulo-portada">
        <div class="portada-texto">
            <h1 id="titulo-portada">
                <span>Mejoramos Santa Cruz</span>
                <span class="portada-acento">entre todos
                    <svg class="portada-trazo" viewBox="0 0 300 18" preserveAspectRatio="none" aria-hidden="true"><path d="M4 13C70 4 190 2 296 9" pathLength="1"/></svg>
                </span>
            </h1>
            <p>Un bache, un foco apagado o basura acumulada: toma una foto, marca el lugar y sigue el avance de tu reclamo con tu propio código, sin crear una cuenta.</p>
            <div class="portada-acciones">
                <a class="boton boton-verde" href="#descarga">
                    <x-fixa.icono nombre="megafono" /> Reportar una incidencia
                </a>
                <a class="boton boton-contorno" href="#que-pasa">
                    <x-fixa.icono nombre="buscar" /> Cómo sigo mi reporte
                </a>
            </div>
        </div>

        <div class="portada-visual">
            <span class="franja-diagonal franja-diagonal-hoja" aria-hidden="true"></span>
            <span class="franja-diagonal franja-diagonal-sol" aria-hidden="true"></span>

            <div class="portada-foto">
                {{-- Santa Cruz de noche desde el aire. Provisional: aquí irá el mapa de OpenStreetMap con los reportes. --}}
                <img class="portada-ciudad" src="{{ recurso('images/santa-cruz-anillos.webp') }}"
                     alt="" width="760" height="640" decoding="async" fetchpriority="high">
            </div>

            {{-- Tarjeta ilustrativa: muestra cómo se ve el seguimiento, no un reporte real. --}}
            <figure class="seguimiento" aria-label="Ejemplo de cómo se sigue un reporte">
                <div class="seguimiento-cabecera">
                    <span class="seguimiento-codigo">FX-2026-000417</span>
                    <span class="seguimiento-etiqueta">Ejemplo</span>
                </div>
                <p class="seguimiento-titulo">Bache en el 3er anillo</p>
                <ol class="seguimiento-pasos">
                    <li class="hecho" style="--i: 0"><span>Recibido</span></li>
                    <li class="hecho" style="--i: 1"><span>En revisión</span></li>
                    <li class="actual" style="--i: 2"><span>Programado</span><small>Cuadrilla asignada</small></li>
                    <li style="--i: 3"><span>Cerrado con evidencia</span></li>
                </ol>
            </figure>
        </div>
    </section>

    <nav class="accesos" aria-label="Qué puedes hacer en FIXA">
        <a class="acceso-rapido" href="#descarga">
            <span class="acceso-rapido-icono"><x-fixa.icono nombre="megafono" /></span>
            <span class="acceso-rapido-texto">
                <strong>Reportar incidencia</strong>
                <span>Avisa de un problema en tu calle desde la app, con foto y ubicación.</span>
            </span>
            <x-fixa.icono nombre="flecha" class="acceso-rapido-flecha" />
        </a>
        <a class="acceso-rapido" href="#que-pasa">
            <span class="acceso-rapido-icono"><x-fixa.icono nombre="buscar" /></span>
            <span class="acceso-rapido-texto">
                <strong>Dar seguimiento</strong>
                <span>Con tu código y clave ves en qué paso está tu reclamo.</span>
            </span>
            <x-fixa.icono nombre="flecha" class="acceso-rapido-flecha" />
        </a>
        <a class="acceso-rapido" href="#otros">
            <span class="acceso-rapido-icono"><x-fixa.icono nombre="letrero" /></span>
            <span class="acceso-rapido-texto">
                <strong>Saber a quién acudir</strong>
                <span>Si tu problema no es de FIXA, te decimos qué entidad lo atiende.</span>
            </span>
            <x-fixa.icono nombre="flecha" class="acceso-rapido-flecha" />
        </a>
    </nav>

    <section class="seccion reportes" id="reportes" aria-labelledby="que-reportar">
        <div class="seccion-cabecera">
            <h2 id="que-reportar">Qué puedes reportar</h2>
            <p>Cada categoría llega directo a la unidad municipal que la resuelve.</p>
        </div>
        <dl class="categorias">
            <div class="categoria">
                <dt><span class="categoria-icono"><x-fixa.icono nombre="via" /></span> Baches y deterioro vial</dt>
                <dd>Lo atiende la Secretaría Municipal de Obras Públicas.</dd>
            </div>
            <div class="categoria">
                <dt><span class="categoria-icono"><x-fixa.icono nombre="foco" /></span> Fallas de alumbrado público</dt>
                <dd>Lo atiende la UOD de Alumbrado Público.</dd>
            </div>
            <div class="categoria">
                <dt><span class="categoria-icono"><x-fixa.icono nombre="basura" /></span> Microbasurales y residuos</dt>
                <dd>Lo atiende EMACRUZ.</dd>
            </div>
        </dl>
    </section>

    <section class="seccion recorrido" id="que-pasa" aria-labelledby="titulo-recorrido">
        <div class="seccion-cabecera">
            <h2 id="titulo-recorrido">Qué pasa después de reportar</h2>
            <p>Cuatro pasos, y en cada uno puedes ver dónde está tu reclamo.</p>
        </div>
        <ol class="pasos">
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

    <section class="seccion otros" id="otros" aria-labelledby="otro-problema">
        <div class="seccion-cabecera seccion-cabecera-sola">
            <h2 id="otro-problema">Si tu problema es otro</h2>
            <p>FIXA solo recibe las tres categorías de arriba. Para estos casos, acude a:</p>
        </div>
        <div class="otros-cuerpo">
            <ul class="entidades">
                <li><strong>CRE</strong> <span>Cortes de luz en tu domicilio y fallas de la red eléctrica.</span></li>
                <li><strong>ABC</strong> <span>Carreteras de la red vial fundamental.</span></li>
                <li><strong>Gobernación de Santa Cruz</strong> <span>Asuntos de competencia departamental.</span></li>
            </ul>
            <p class="aviso">
                <x-fixa.icono nombre="alerta" />
                FIXA no atiende emergencias. Si hay peligro inmediato, llama a los servicios de emergencia: <b>800125050</b> 
            </p>
        </div>
    </section>

    <section class="descarga" id="descarga" aria-labelledby="titulo-descarga">
        <svg class="descarga-anillos" viewBox="0 0 400 400" aria-hidden="true">
            @foreach ([40, 80, 125, 170, 215] as $radio)
                <circle cx="200" cy="200" r="{{ $radio }}"/>
            @endforeach
        </svg>
        <div class="descarga-texto">
            <h2 id="titulo-descarga">Reporta desde tu celular</h2>
            @if (filled(config('fixa.app_url')))
                <p>La app funciona en celulares Android. Descárgala, toma la foto y marca el lugar.</p>
                <a class="boton boton-sol" href="{{ config('fixa.app_url') }}">
                    <x-fixa.icono nombre="descargar" /> Descargar la app para Android
                </a>
            @else
                <p>La app para Android estará disponible pronto en esta página.</p>
                <span class="boton boton-inactivo" role="link" aria-disabled="true">
                    <x-fixa.icono nombre="celular" /> Descargar la app para Android
                </span>
            @endif
        </div>
        <img class="descarga-marca" src="{{ recurso('images/fixa-isotipo.webp') }}" alt="" width="344" height="240" loading="lazy">
    </section>
</main>

<footer class="pie">
    <img class="pie-logo" src="{{ recurso('images/fixa-logo.webp') }}" alt="FIXA" width="144" height="40" loading="lazy">
    <p class="pie-texto">No es un canal oficial del Gobierno Autónomo Municipal.</p>
    <ul class="pie-enlaces">
        <li><a href="#reportes">Qué reportar</a></li>
        <li><a href="#que-pasa">Cómo funciona</a></li>
        <li><a href="{{ url('/admin') }}">Iniciar sesión</a></li>
    </ul>
    
</footer>
@endsection

@push('scripts')
<script>
    // La barra gana sombra al bajar, para separarse del contenido.
    (() => {
        const barra = document.querySelector('.barra');
        const marcar = () => barra.classList.toggle('barra-fija', window.scrollY > 40);
        marcar();
        window.addEventListener('scroll', marcar, { passive: true });
    })();

    // Recorrido: los pasos se animan una sola vez, cuando la lista entra en pantalla.
    (() => {
        const pasos = document.querySelector('.pasos');
        if (!pasos || !('IntersectionObserver' in window)
            || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        pasos.classList.add('pasos-anima');
        const observador = new IntersectionObserver((entradas) => {
            if (!entradas.some((e) => e.isIntersecting)) return;
            pasos.classList.add('en-vista');
            observador.disconnect();
            // Cuando termina la entrada, el hover deja de heredar los retrasos escalonados.
            setTimeout(() => pasos.classList.add('pasos-listo'), 2000);
        }, { threshold: 0.25 });
        observador.observe(pasos);
    })();
</script>
<script src="{{ recurso('movil/js/menu.js') }}" defer></script>
@endpush
