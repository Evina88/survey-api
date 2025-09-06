<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveySubmissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/surveys',       [SurveyController::class, 'index']);
Route::get('/surveys/{id}',  [SurveyController::class, 'show']);

Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::post('/surveys/{id}/submit', [SurveySubmissionController::class, 'submit']);
    Route::get('/me', [AuthController::class, 'me']);
});
