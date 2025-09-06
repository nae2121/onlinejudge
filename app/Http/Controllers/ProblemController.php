# app/Http/Controllers/ProblemController.php
<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use Inertia\Inertia;

class ProblemController extends Controller
{
    public function index() {
        $problems = Problem::select('id','slug','title','time_limit_ms','memory_limit_mb','allowed_langs','scoring')->get();
        return Inertia::render('Problems/Index', ['problems' => $problems]);
    }

    public function show(string $slug) {
        $problem = Problem::where('slug', $slug)->firstOrFail();
        return Inertia::render('Problems/Show', ['problem' => $problem]);
    }
}
