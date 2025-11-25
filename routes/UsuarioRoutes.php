<?php

use App\Http\Controllers\FlatControllers\UsuarioFlatController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::controller(UsuarioController::class)->middleware(['auth:sanctum','solo_superadmin'])->prefix('usuario')->group(function () {
    Route::get('/get-pageable', 'index'); //listo
    Route::post('/', 'store'); //listo
    Route::put('/{id}', 'update'); //listo
    Route::put('/{id}/password', 'cambiarPassword');
    Route::put('/{id}/roles', 'updateRoles');
    Route::delete('/{id}', 'toggleHabilitado');
});
Route::controller(UsuarioFlatController::class)->middleware(['auth:sanctum','solo_superadmin'])->prefix('usuario/flat')->group(function () {
    Route::get('/get-pageable', 'index');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
});