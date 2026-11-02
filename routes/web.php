<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');

    Route::resource('exams', Admin\ExamController::class);
    // Nested + scoped: /exams/{exam}/questions/{question} only resolves when the question
    // belongs to that exam, and a soft-deleted exam returns 404 for its questions too.
    Route::resource('exams.questions', Admin\QuestionController::class)
        ->except(['index', 'show'])
        ->scoped();
});

require __DIR__.'/auth.php';
