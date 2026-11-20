<?php

if (! function_exists('recurso')) {
    function recurso(string $ruta): string
    {
        static $versiones = [];

        $version = $versiones[$ruta] ??= (@filemtime(public_path($ruta)) ?: 0);

        return asset($ruta).($version ? '?v='.$version : '');
    }
}
