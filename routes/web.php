<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/assessments/start', [AssessmentController::class, 'start'])->name('assessments.start');
    Route::get('/assessments/{assessment}/q/{index}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::post('/assessments/{assessment}/q/{index}', [AssessmentController::class, 'answer'])->name('assessments.answer');
    Route::post('/assessments/{assessment}/submit', [AssessmentController::class, 'submit'])->name('assessments.submit');

    Route::get('/results/{assessment}', [ResultController::class, 'show'])->name('results.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
