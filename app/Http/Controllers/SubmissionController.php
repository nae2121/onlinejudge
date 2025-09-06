# app/Http/Controllers/SubmissionController.php
<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Models\Submission;
use App\Jobs\Judge\JudgeSubmissionJob;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function store(Request $request, string $slug) {
        $request->validate([
            'lang' => 'required|string',
            'code' => 'required|string|max:200000',
        ]);

        $problem = Problem::where('slug', $slug)->firstOrFail();
        $sub = Submission::create([
            'user_id'   => optional($request->user())->id,
            'problem_id'=> $problem->id,
            'lang'      => $request->input('lang'),
            'code'      => $request->input('code'),
            'status'    => 'QUEUED',
        ]);

        JudgeSubmissionJob::dispatch($sub->id);

        if ($request->wantsJson()) {
            return response()->json(['submission_id' => $sub->id], 201);
        }
        return redirect()->route('problems.show', ['slug'=>$problem->slug])->with('submission_id', $sub->id);
    }

    public function show(int $id) {
        return response()->json(Submission::findOrFail($id));
    }
}
