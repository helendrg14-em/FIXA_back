<?php

if (! function_exists('recurso')) {
    /**
     * URL de un archivo de public/ con su versión (?v=fecha de modificación).
     * Apache lo guarda en caché del navegador por un año (docker/apache.conf);
     * cuando el archivo cambia, cambia la URL y el navegador baja la versión nueva.
     */
    function recurso(string $ruta): string
    {
        static $versiones = [];

        $version = $versiones[$ruta] ??= (@filemtime(public_path($ruta)) ?: 0);

        return asset($ruta).($version ? '?v='.$version : '');
    }
}
