# app/Services/Judge/IsolateJudge.php
<?php

namespace App\Services\Judge;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;

class IsolateJudge
{
    protected array $langConf;
    protected int $memKbDefault;
    protected int $timeMsDefault;

    public function __construct()
    {
        $this->langConf      = config('judge.languages');
        $this->timeMsDefault = (int) config('judge.time_limit_ms_default', 2000);
        $this->memKbDefault  = (int) config('judge.memory_limit_mb_default', 256) * 1024;
    }

    public function judge(string $code, string $lang, string $slug, int $timeLimitMs = null, int $memoryLimitMb = null, array $scoring = []): array
    {
        if (!isset($this->langConf[$lang])) {
            return ['status'=>'RE','message'=>'unsupported language'];
        }

        $timeLimitMs = $timeLimitMs ?: $this->timeMsDefault;
        $memKb       = (int) (($memoryLimitMb ?: ($this->memKbDefault/1024)) * 1024);

        $tmp = storage_path('app/runs/'.Str::random(16));
        @mkdir($tmp, 0777, true);

        $base = storage_path('app/problems/'.$slug);
        $yaml = $base.'/problem.yml';
        if (!file_exists($yaml)) {
            $this->rrmdir($tmp);
            return ['status'=>'RE','message'=>'problem.yml not found'];
        }
        $conf  = Yaml::parseFile($yaml);
        $tests = $conf['tests'] ?? [];
        if (!$tests) {
            $this->rrmdir($tmp);
            return ['status'=>'RE','message'=>'no tests'];
        }

        // write source
        $langSpec = $this->langConf[$lang];
        file_put_contents($tmp.'/'.$langSpec['src'], $code);

        // compile if needed
        $compiled = true;
        if (!empty($langSpec['compile'])) {
            $res = $this->runIsolate(
                argv: $langSpec['compile'],
                timeMs: max(3000, $timeLimitMs + 1000),
                memKb: max($memKb, 512*1024),
                boxDirs: $this->defaultDirs($tmp),
                stdin: null,
                stdout: null
            );
            if ($res['code'] !== 0) {
                $this->rrmdir($tmp);
                return ['status'=>'CE','stderr'=>mb_substr($res['stderr'] ?? '', 0, 2000)];
            }
        }

        $groupOk = [];
        foreach ($tests as $t) $groupOk[$t['group'] ?? 'default'] = true;

        $caseResults = [];
        $maxTime = 0;

        foreach ($tests as $i => $t) {
            $inPath  = $base . '/' . $t['in'];
            $outPath = $base . '/' . $t['out'];
            $inp     = file_get_contents($inPath);
            $expect  = file_get_contents($outPath);

            file_put_contents($tmp.'/in.txt', $inp);
            @unlink($tmp.'/out.txt');

            $runRes = $this->runIsolate(
                argv: $langSpec['run'],
                timeMs: $timeLimitMs,
                memKb:  $memKb,
                boxDirs: $this->defaultDirs($tmp),
                stdin: '/work/in.txt',
                stdout:'/work/out.txt'
            );

            $maxTime = max($maxTime, $runRes['elapsed_ms']);
            $group   = $t['group'] ?? 'default';

            if ($runRes['code'] === 124) {
                $caseResults[] = ['idx'=>$i+1,'verdict'=>'TLE','time_ms'=>$runRes['elapsed_ms']];
                $groupOk[$group] = false;
                continue;
            }
            if ($runRes['code'] !== 0) {
                $caseResults[] = ['idx'=>$i+1,'verdict'=>'RE','stderr'=>mb_substr($runRes['stderr'] ?? '', 0, 300)];
                $groupOk[$group] = false;
                continue;
            }

            $actual = @file_get_contents($tmp.'/out.txt') ?? '';
            if (trim($actual) === trim($expect)) {
                $caseResults[] = ['idx'=>$i+1,'verdict'=>'AC','time_ms'=>$runRes['elapsed_ms']];
            } else {
                $caseResults[] = ['idx'=>$i+1,'verdict'=>'WA','time_ms'=>$runRes['elapsed_ms']];
                $groupOk[$group] = false;
            }
        }

        // scoring
        [$status, $points] = $this->score($caseResults, $groupOk, $scoring);

        $this->rrmdir($tmp);
        return [
            'status'      => $status,
            'points'      => $points,
            'time_ms'     => $maxTime,
            'case_results'=> $caseResults,
            'group_ok'    => $groupOk,
        ];
    }

    protected function score(array $caseResults, array $groupOk, array $scoring): array
    {
        $status = 'AC';
        $points = 0;

        if (($scoring['type'] ?? '') === 'sum_subtasks') {
            $total = 0;
            foreach (($scoring['groups'] ?? []) as $g => $p) {
                $p = max(0, (int)$p);
                $total += $p;
                if ($p > 0 && ($groupOk[$g] ?? false)) $points += $p;
            }
            if ($points === $total) $status = 'AC';
            elseif ($points > 0)   $status = 'PARTIAL';
            else {
                $hasWAorTLE = collect($caseResults)->contains(fn($r)=>in_array($r['verdict'],['WA','TLE']));
                $status = $hasWAorTLE ? 'WA' : 'RE';
            }
        } else {
            $allOk = collect($caseResults)->every(fn($r)=>$r['verdict']==='AC');
            $status = $allOk ? 'AC' : (collect($caseResults)->contains(fn($r)=>in_array($r['verdict'],['WA','TLE'])) ? 'WA' : 'RE');
            $points = $allOk ? ($scoring['points'] ?? 100) : 0;
        }

        return [$status, $points];
    }

    protected function defaultDirs(string $hostWork): array
    {
        // ホスト -> サンドボックス マウント
        return [
            '--dir=/usr=/usr:ro',
            '--dir=/lib=/lib:ro',
            '--dir=/lib64=/lib64:ro',
            '--dir=/bin=/bin:ro',
            '--dir=/etc=/etc:ro',
            '--dir=/dev=/dev:ro',
            '--dir=/tmp=/tmp',
            '--dir='.$hostWork.'=/work:rw',
        ];
    }

    protected function runIsolate(array $argv, int $timeMs, int $memKb, array $boxDirs, ?string $stdin, ?string $stdout): array
    {
        $boxId = $this->allocBox();
        try {
            $cmd = array_merge(
                ['isolate','--cg','-b', (string)$boxId,
                 '--time', (string)max(1, $timeMs/1000),
                 '--mem',  (string)max(32768, $memKb),
                 '--processes','128'],
                $boxDirs
            );

            if ($stdin)  { $cmd[] = '--stdin='.$stdin; }
            if ($stdout) { $cmd[] = '--stdout='.$stdout; }

            $cmd = array_merge($cmd, ['--run','--'], $argv);

            $start = microtime(true);
            $proc  = new Process($cmd, timeout: ($timeMs/1000.0)+1.5);
            try {
                $proc->run();
                $elapsed = (int) round((microtime(true)-$start)*1000);
                return [
                    'code'       => $proc->getExitCode(),
                    'elapsed_ms' => $elapsed,
                    'stdout'     => $proc->getOutput(),
                    'stderr'     => $proc->getErrorOutput(),
                ];
            } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
                return ['code'=>124,'elapsed_ms'=>$timeMs+1,'stdout'=>'','stderr'=>'TIMEOUT'];
            }
        } finally {
            $this->cleanupBox($boxId);
        }
    }

    protected function allocBox(): int
    {
        for ($i=0; $i<1000; $i++) {
            $p = new Process(['isolate','--cg','--init','-b',(string)$i]);
            $p->run();
            if ($p->getExitCode() === 0) return $i;
        }
        throw new \RuntimeException('no free isolate box');
    }

    protected function cleanupBox(int $id): void
    {
        $p = new Process(['isolate','--cg','-b',(string)$id,'--cleanup']);
        $p->run();
    }

    protected function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) {
            $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
        }
        @rmdir($dir);
    }
}
