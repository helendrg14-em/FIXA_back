<?php

use App\Http\Controllers\Cuenta\RegistrarPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('publico.portal');
})->name('portal');

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/cuenta/{usuario}/contrasena', [RegistrarPasswordController::class, 'show'])->whereNumber('usuario')->name('cuenta.contrasena');
    Route::post('/cuenta/{usuario}/contrasena', [RegistrarPasswordController::class, 'store'])->whereNumber('usuario')->name('cuenta.contrasena.guardar');
});
