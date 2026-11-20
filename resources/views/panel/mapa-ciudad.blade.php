{{-- Mapa de reportes de la ciudad (App\Filament\Pages\Mapa). Un pin por caso, del color de su categoría. --}}
@php
    $colores = ['bache' => '#d0600b', 'alumbrado' => '#c99a06', 'residuos' => '#1f7d47'];
@endphp

<x-filament-panels::page>
    <div class="fx-mapa-filtros">
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="estado" aria-label="Estado">
                <option value="abiertos">Casos abiertos</option>
                <option value="cerrados">Cerrados, rechazados y derivados</option>
                <option value="todos">Todos</option>
            </x-filament::input.select>
        </x-filament::input.wrapper>

        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="categoria" aria-label="Categoría">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        <ul class="fx-mapa-leyenda">
            @foreach ($categorias as $cat)
                <li><span style="background: {{ $colores[$cat->codigo?->value] ?? '#677a64' }}"></span>{{ $cat->nombre }}</li>
            @endforeach
        </ul>
    </div>

    <div
        wire:ignore
        x-data="{
            mapa: null,
            capa: null,
            total: 0,
            colores: @js($colores),
            iniciar(puntos) {
                this.mapa = L.map(this.$refs.mapa).setView([-17.7834, -63.1821], 13);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(this.mapa);
                this.capa = L.layerGroup().addTo(this.mapa);
                this.pintar(puntos);
                setTimeout(() => this.mapa.invalidateSize(), 200);
            },
            pintar(puntos) {
                this.capa.clearLayers();
                this.total = puntos.length;
                for (const p of puntos) {
                    L.marker([p.lat, p.lng], { icon: this.pin(p), riseOnHover: true })
                        .bindPopup(() => this.ficha(p))
                        .addTo(this.capa);
                }
                if (puntos.length) {
                    this.mapa.fitBounds(puntos.map((p) => [p.lat, p.lng]), { padding: [40, 40], maxZoom: 16 });
                }
            },
            // Pin en forma de gota, del color de la categoría. Los vencidos van más grandes y con borde rojo;
            // los cerrados, atenuados. El color sale de la lista fija de arriba, nunca de la base.
            pin(p) {
                const ancho = p.vencido ? 34 : 28;
                const alto = Math.round(ancho * 4 / 3);
                const color = this.colores[p.codigo] ?? '#677a64';
                const borde = p.vencido ? '#b42318' : '#ffffff';
                const grosor = p.vencido ? 2 : 1.5;
                const svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='-2 -2 28 36' width='${ancho}' height='${alto}'>`
                    + `<path d='M12 0C5.4 0 0 5.3 0 11.9 0 20.8 12 32 12 32s12-11.2 12-20.1C24 5.3 18.6 0 12 0z' fill='${color}' stroke='${borde}' stroke-width='${grosor}'/>`
                    + `<circle cx='12' cy='11.9' r='4.6' fill='#ffffff'/></svg>`;
                return L.divIcon({
                    html: svg,
                    className: 'fx-pin' + (p.cerrado ? ' fx-pin-cerrado' : ''),
                    iconSize: [ancho, alto],
                    iconAnchor: [ancho / 2, alto - 1],
                    popupAnchor: [0, -alto + 4],
                });
            },
            // Se arma con nodos y textContent, no con HTML, para no interpretar lo que venga de la base.
            ficha(p) {
                const caja = document.createElement('div');
                const linea = (texto, negrita = false) => {
                    const el = document.createElement(negrita ? 'strong' : 'div');
                    el.textContent = texto;
                    caja.append(el, document.createElement('br'));
                };
                linea('Caso #' + p.id, true);
                linea(p.categoria ?? 'Sin categoría');
                linea('Estado: ' + p.estado + (p.vencido ? ' · vencido' : ''));
                linea('Prioridad: ' + (p.prioridad ?? 'sin asignar'));
                const enlace = document.createElement('a');
                enlace.href = p.url;
                enlace.textContent = 'Abrir el caso';
                caja.append(enlace);
                return caja;
            },
        }"
        x-init="iniciar(@js($puntos))"
        x-on:mapa-puntos.window="pintar($event.detail.puntos)"
    >
        <p class="fx-mapa-total" x-text="total === 1 ? '1 caso en el mapa' : total + ' casos en el mapa'"></p>
        <div x-ref="mapa" class="fx-mapa-ciudad"></div>
    </div>
</x-filament-panels::page>
