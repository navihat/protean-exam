<?php

use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExamController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
        Route::post('/exams/{exam}/attempts', [AttemptController::class, 'store'])->name('attempts.store');

        Route::get('/attempts/{attempt}', [AttemptController::class, 'show'])
            ->middleware('can:view,attempt')
            ->name('attempts.show');
        Route::post('/attempts/{attempt}/submit', [AttemptController::class, 'submit'])
            ->middleware('can:submit,attempt')
            ->name('attempts.submit');
        Route::get('/attempts/{attempt}/result', [AttemptController::class, 'result'])
            ->middleware('can:view,attempt')
            ->name('attempts.result');
    });
});
