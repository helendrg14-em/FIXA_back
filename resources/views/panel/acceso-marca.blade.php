{{-- Columna de marca del login del panel (render hook SIMPLE_LAYOUT_START). --}}
<aside class="fx-login-marca">
    <img class="fx-login-logo" src="{{ recurso('images/fixa-logo-claro.webp') }}" alt="FIXA" width="144" height="40">

    <div class="fx-login-texto">
        <h2>Cada reporte de la ciudad, atendido hasta el cierre.</h2>
        <p>Panel de trabajo para el equipo municipal que recibe, prioriza y resuelve los reportes ciudadanos.</p>

        <ul class="fx-login-perfiles">
            <li><strong>Administración</strong><span>Cuentas, categorías, plazos y feriados.</span></li>
            <li><strong>Supervisión</strong><span>Prioriza y asigna los casos de su unidad.</span></li>
            <li><strong>Técnicos</strong><span>Atienden la agenda de su cuadrilla.</span></li>
        </ul>
    </div>

    <p class="fx-login-nota">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.4 3.1 8.4 7.5 9.5 4.4-1.1 7.5-5.1 7.5-9.5V6L12 3Z"/><path d="m9 12 2 2 4-4"/></svg>
        La sesión se cierra sola tras 15 minutos sin actividad.
    </p>

    <x-fixa.ciudad class="fx-login-ciudad" />
</aside>
