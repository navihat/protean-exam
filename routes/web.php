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
    // Shallow: create/store under /exams/{exam}/questions, edit/update/destroy under /questions/{question}.
    Route::resource('exams.questions', Admin\QuestionController::class)
        ->except(['index', 'show'])
        ->shallow();
});

require __DIR__.'/auth.php';
