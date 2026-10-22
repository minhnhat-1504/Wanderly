<?php

use App\Http\Controllers\Trip\ActivityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AJAX Routes for Wanderly
|--------------------------------------------------------------------------
|
| These routes are loaded by bootstrap/app.php with the "web" middleware group
| and prefix "ajax" and name "ajax.".
|
*/

Route::middleware('auth')->group(function () {
    Route::put('/trips/{trip}/activities/reorder', [ActivityController::class, 'reorder'])
        ->name('trips.activities.reorder');

    Route::post('/trips/{trip}/activities', [ActivityController::class, 'store'])
        ->name('trips.activities.store');

    Route::match(['put', 'patch'], '/trips/{trip}/activities/{activity}', [ActivityController::class, 'update'])
        ->name('trips.activities.update');

    Route::delete('/trips/{trip}/activities/{activity}', [ActivityController::class, 'destroy'])
        ->name('trips.activities.destroy');
});
