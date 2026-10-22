<?php

use App\Http\Controllers\Trip\RoomBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AJAX Routes for Wanderly
|--------------------------------------------------------------------------
|
| Here is where you can register AJAX routes for the Wanderly application.
| These routes are loaded by bootstrap/app.php within the "web" and "ajax." group.
|
*/

Route::middleware('auth')->group(function () {
    Route::patch('/rooms/{room}/toggle-budget', [RoomBookingController::class, 'toggleBudget'])->name('rooms.toggle-budget');
});
