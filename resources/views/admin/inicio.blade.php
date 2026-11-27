@php
    use Filament\Support\Icons\Heroicon;

    $maxEquipo = max(1, $equipo->max('total'));
@endphp

<x-filament-widgets::widget class="fx-inicio">
    <x-fixa.saludo :saludo="$saludo" :nombre="$usuario->nombres" perfil="Administrador" :fecha="$fecha">
        <x-slot:acciones>
            <x-filament::button tag="a" :href="$urlNuevoUsuario" :icon="Heroicon::OutlinedUserPlus" size="lg" class="fx-boton-lima">
                Crear cuenta
            </x-filament::button>
        </x-slot:acciones>
    </x-fixa.saludo>

    <div class="fx-rejilla fx-rejilla-admin">
        <div class="fx-columna">
            <x-fixa.bloque titulo="Equipo" descripcion="Cuentas activas que pueden entrar al panel." :enlace="$urlUsuarios" texto-enlace="Gestionar usuarios">
                <div class="fx-equipo">
                    <p class="fx-cifra">
                        <span class="fx-cifra-valor">{{ $totalActivos }}</span>
                        <span class="fx-cifra-texto">{{ $totalActivos === 1 ? 'cuenta activa' : 'cuentas activas' }}@if ($inactivos) · {{ $inactivos }} {{ $inactivos === 1 ? 'desactivada' : 'desactivadas' }}@endif</span>
                    </p>

                    <ul class="fx-barras">
                        @foreach ($equipo as $fila)
                            <li style="--valor: {{ $fila['total'] / $maxEquipo }}; --i: {{ $loop->index }}">
                                <span class="fx-barras-nombre">{{ $fila['perfil'] }}</span>
                                <span class="fx-barras-pista"><span class="fx-barras-relleno"></span></span>
                                <span class="fx-barras-total">{{ $fila['total'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="fx-subbloque">
                    <h3>
                        Esperando crear su contraseña
                        @if ($totalSinContrasena)
                            <span class="fx-chip fx-chip-naranja">{{ $totalSinContrasena }}</span>
                        @endif
                    </h3>

                    @forelse ($sinContrasena as $persona)
                        <div class="fx-fila">
                            <span class="fx-avatar" aria-hidden="true">{{ mb_substr($persona->nombres, 0, 1) }}{{ mb_substr($persona->apellidos, 0, 1) }}</span>
                            <span class="fx-fila-principal">
                                <strong>{{ $persona->nombres }} {{ $persona->apellidos }}</strong>
                                <span>{{ $persona->rol->getLabel() }}</span>
                            </span>
                            <span class="fx-fila-meta">
                                Cuenta creada {{ $persona->fecha_creacion?->locale('es')->diffForHumans() }}
                            </span>
                        </div>
                    @empty
                        <x-fixa.vacio titulo="Todas las cuentas activas ya tienen contraseña." :icono="Heroicon::OutlinedCheckBadge" />
                    @endforelse

                    @if ($totalSinContrasena)
                        <p class="fx-ayuda">El enlace vence a las 48 horas. Desde Usuarios puedes reenviarlo.</p>
                    @endif
                </div>
            </x-fixa.bloque>

            <x-fixa.bloque titulo="Casos en curso" descripcion="Todas las unidades, sin contar cerrados, rechazados, duplicados ni derivados." :enlace="$urlCasos" texto-enlace="Gestionar casos">
                <dl class="fx-datos">
                    <div>
                        <dt>Abiertos</dt>
                        <dd>{{ $casosAbiertos }}</dd>
                    </div>
                    <div @class(['fx-datos-alerta' => $casosVencidos > 0])>
                        <dt>Fuera de plazo</dt>
                        <dd>{{ $casosVencidos }}</dd>
                    </div>
                </dl>
            </x-fixa.bloque>
        </div>

        <div class="fx-columna">
            <x-fixa.bloque titulo="Operación">
                <nav class="fx-accesos" aria-label="Operación">
                    @foreach ($operacion as $acceso)
                        <a class="fx-acceso" href="{{ $acceso['url'] }}">
                            <span class="fx-acceso-icono"><x-filament::icon :icon="$acceso['icono']" /></span>
                            <span class="fx-acceso-texto"><strong>{{ $acceso['titulo'] }}</strong></span>
                            <x-filament::icon :icon="Heroicon::ChevronRight" class="fx-acceso-flecha" />
                        </a>
                    @endforeach
                </nav>
            </x-fixa.bloque>

            <x-fixa.bloque titulo="Configuración" descripcion="Lo que define cómo se atienden los reportes.">
                <nav class="fx-accesos" aria-label="Configuración">
                    <a class="fx-acceso" href="{{ $urlCategorias }}">
                        <span class="fx-acceso-icono"><x-filament::icon :icon="Heroicon::OutlinedTag" /></span>
                        <span class="fx-acceso-texto">
                            <strong>Gestionar categorías</strong>
                            <span>{{ $categoriasActivas }} de {{ $categorias }} {{ $categorias === 1 ? 'activa' : 'activas' }}</span>
                        </span>
                        <x-filament::icon :icon="Heroicon::ChevronRight" class="fx-acceso-flecha" />
                    </a>
                    <a class="fx-acceso" href="{{ $urlUsuarios }}">
                        <span class="fx-acceso-icono"><x-filament::icon :icon="Heroicon::OutlinedUsers" /></span>
                        <span class="fx-acceso-texto">
                            <strong>Usuarios</strong>
                            <span>Editar, activar y restablecer contraseñas</span>
                        </span>
                        <x-filament::icon :icon="Heroicon::ChevronRight" class="fx-acceso-flecha" />
                    </a>
                </nav>
            </x-fixa.bloque>
        </div>
    </div>
</x-filament-widgets::widget>
