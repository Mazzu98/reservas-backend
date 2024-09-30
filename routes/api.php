<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Middlewares\IsAdmin;
use App\Http\Middlewares\IsOwnReservation;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
  Route::post('logout', [AuthController::class, 'logout']);
  Route::get('user', [AuthController::class, 'user']);
  Route::get('spaces/{id}/daily-available-slots', [SpaceController::class, 'getDailyAvailableTimeSlots']);
  Route::get('spaces/{id}/weekly-available-slots', [SpaceController::class, 'getWeeklyAvailableTimeSlots']);

  Route::prefix('reservation')->group(function () {
    Route::post('', [ReservationController::class, 'store']);
    Route::get('', [ReservationController::class, 'get']);
    Route::prefix('{id}')->middleware(IsOwnReservation::class)->group(function () {
      Route::get('', [ReservationController::class, 'getById']);
      Route::put('', [ReservationController::class, 'update']);
      Route::delete('', [ReservationController::class, 'delete']);
    });
  });

  Route::get('space/search', [SpaceController::class, 'query']);

  Route::prefix('space')->middleware(IsAdmin::class)->group(function () {
    Route::post('', [SpaceController::class, 'store']);
    Route::get('', [SpaceController::class, 'get']);
    Route::prefix('{id}')->group(function () {
      Route::get('', [SpaceController::class, 'getById']);
      Route::patch('', [SpaceController::class, 'update']);
      Route::delete('', [SpaceController::class, 'delete']);
    });
  });

});