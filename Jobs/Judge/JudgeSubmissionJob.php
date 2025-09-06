# app/Jobs/Judge/JudgeSubmissionJob.php
<?php

namespace App\Jobs\Judge;

use App\Models\Submission;
use App\Models\Problem;
use App\Services\Judge\IsolateJudge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JudgeSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $submissionId) {}

    public function handle(): void
    {
        $sub = Submission::find($this->submissionId);
        if (!$sub) return;

        $sub->status = 'RUNNING';
        $sub->save();

        $problem = Problem::findOrFail($sub->problem_id);

        $judge  = new IsolateJudge();
        $result = $judge->judge(
            code: $sub->code,
            lang: $sub->lang,
            slug: $problem->slug,
            timeLimitMs: $problem->time_limit_ms,
            memoryLimitMb: $problem->memory_limit_mb,
            scoring: $problem->scoring ?? ['type'=>'all_or_nothing','points'=>100]
        );

        $sub->status  = $result['status']  ?? 'RE';
        $sub->points  = $result['points']  ?? 0;
        $sub->time_ms = $result['time_ms'] ?? null;
        $sub->detail  = $result;
        $sub->save();
    }
}
