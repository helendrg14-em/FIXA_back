
<div
    wire:ignore
    x-data="{
        lat: $wire.entangle('data.latitud'),
        lng: $wire.entangle('data.longitud'),
        mapa: null,
        pin: null,
        iniciar() {
            const inicio = [Number(this.lat) || -17.7834, Number(this.lng) || -63.1821];
            this.mapa = L.map(this.$refs.mapa).setView(inicio, 16);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this.mapa);
            this.pin = L.marker(inicio, { draggable: true }).addTo(this.mapa);
            this.pin.on('dragend', () => this.fijar(this.pin.getLatLng()));
            this.mapa.on('click', (e) => this.fijar(e.latlng));
            this.$watch('lat', () => this.mover());
            this.$watch('lng', () => this.mover());
            this.fijar(this.pin.getLatLng());
            // El mapa se dibuja dentro de una sección: hay que recalcular su tamaño al mostrarse.
            setTimeout(() => this.mapa.invalidateSize(), 200);
        },
        fijar(punto) {
            this.pin.setLatLng(punto);
            this.lat = Number(punto.lat.toFixed(6));
            this.lng = Number(punto.lng.toFixed(6));
        },
        mover() {
            const punto = [Number(this.lat), Number(this.lng)];
            if (!isNaN(punto[0]) && !isNaN(punto[1])) {
                this.pin.setLatLng(punto);
                this.mapa.panTo(punto);
            }
        },
    }"
    x-init="iniciar()"
>
    <div x-ref="mapa" style="height: clamp(220px, 32vh, 300px); border-radius: 12px; border: 1px solid #dbe3d5; z-index: 0;"></div>
    <p style="margin-top: .375rem; font-size: .8125rem; color: #4f614d;">
        Toca el mapa o arrastra el pin hasta el lugar exacto del problema.
    </p>
</div>
