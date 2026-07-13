<?php

declare(strict_types=1);

namespace App\Repositories;

final class CronJobRunRepository extends BaseRepository
{
    protected string $table = 'cron_job_runs';
    protected bool $usesSoftDeletes = false;

    public function start(string $jobName): int
    {
        return (int) $this->create([
            'job_name' => $jobName,
            'status' => 'running',
            'started_at' => now_utc()->format('Y-m-d H:i:s'),
        ]);
    }

    public function finish(int $id, bool $success, string $output = ''): void
    {
        $this->update($id, [
            'status' => $success ? 'success' : 'failed',
            'finished_at' => now_utc()->format('Y-m-d H:i:s'),
            'output' => mb_substr($output, 0, 5000),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>> latest run per job name
     */
    public function latestPerJob(): array
    {
        $rows = \App\Core\Database\QueryBuilder::table('cron_job_runs')
            ->withoutSoftDeletes()
            ->orderBy('id', 'DESC')
            ->get();

        $latest = [];

        foreach ($rows as $row) {
            if (!isset($latest[$row['job_name']])) {
                $latest[$row['job_name']] = $row;
            }
        }

        return $latest;
    }
}
