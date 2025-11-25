<?php
use App\Http\Controllers\RolController;
use Illuminate\Support\Facades\Route;

Route::controller(RolController::class)->middleware(['auth:sanctum','solo_admin'])->prefix('rol')->group(function () {
    Route::get('/', 'index');
});