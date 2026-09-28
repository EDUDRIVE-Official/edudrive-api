<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return redirect()->route(auth()->check() ? 'student-profile.show' : 'login');
})->name('home');

Route::get('/design-system', function () {
    return view('design-system');
})->name('design-system');

if (app()->environment('local')) {
    Route::view('/pruebas/rampa-bloqueada-3d', 'courses.blocked-ramp-preview')->name('blocked-ramp.preview');
    Route::view('/pruebas/dos-caminos-3d', 'courses.route-decision-preview')->name('route-decision.preview');
    Route::view('/pruebas/rutas-3d', 'courses.route-comparison-preview')->name('route-comparison.preview');
    Route::view('/pruebas/movimientos-cruzados-3d', 'courses.crossing-movements-preview')->name('crossing-movements.preview');
    Route::view('/pruebas/autobus-parada-3d', 'courses.bus-stop-preview')->name('bus-stop.preview');
    Route::view('/pruebas/actores-3d', 'courses.actors-preview')->name('actors.preview');
    Route::view('/pruebas/cruce-3d', 'courses.crossing-preview')->name('crossing.preview');
}
