# routes/api.php
<?php

use Illuminate\Support\Facades\Route;
use App\Models\Problem;
use App\Http\Controllers\SubmissionController;

Route::get('/problems', fn()=> Problem::select('id','slug','title','time_limit_ms','memory_limit_mb','allowed_langs','scoring')->get());
Route::get('/problems/{slug}', fn(string $slug)=> Problem::where('slug',$slug)->firstOrFail());
Route::get('/submissions/{id}', [SubmissionController::class, 'show']);
