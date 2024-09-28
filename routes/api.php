<?php
use Illuminate\Support\Facades\Route; // Add this line
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpaceController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
  Route::post('logout', [AuthController::class, 'logout']);
  Route::get('user', [AuthController::class, 'user']);
  Route::get('/spaces/{id}/daily-available-slots', [SpaceController::class, 'getDailyAvailableTimeSlots']);
  Route::get('/spaces/{id}/weekly-available-slots', [SpaceController::class, 'getWeeklyAvailableTimeSlots']);
});