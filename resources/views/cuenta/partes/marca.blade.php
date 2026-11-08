<aside class="acceso-marca">
    <span class="acceso-isotipo">
        <img src="{{ recurso('images/fixa-isotipo.webp') }}" alt="" width="40" height="40">
    </span>
    <div>
        <p class="acceso-etiqueta">{{ $etiqueta ?? 'Equipo FIXA' }}</p>
        <h2>{{ $titulo ?? 'Te damos la bienvenida al equipo de FIXA.' }}</h2>
        <p>{{ $texto ?? 'Desde el panel recibes, priorizas y cierras los reportes de los vecinos de Santa Cruz.' }}</p>
    </div>
    <p class="acceso-nota">Panel de funcionarios · Santa Cruz de la Sierra</p>
    <x-fixa.ciudad class="acceso-ciudad" />
</aside>
