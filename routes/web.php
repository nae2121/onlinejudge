# routes/web.php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\SubmissionController;

Route::get('/', fn()=>redirect('/problems'));
Route::get('/problems', [ProblemController::class, 'index'])->name('problems.index');
Route::get('/problems/{slug}', [ProblemController::class, 'show'])->name('problems.show');
Route::post('/problems/{slug}/submit', [SubmissionController::class, 'store'])->name('submissions.store');
