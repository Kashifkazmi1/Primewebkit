#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Repositories\CronJobRunRepository;
use App\Services\CrawlJobProcessor;

/** @var App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$container = $app->container();

$limit = isset($argv[1]) ? (int) $argv[1] : 5;

fwrite(STDOUT, "AI Chatbot SaaS — Crawl Job Processor\n");
fwrite(STDOUT, str_repeat('-', 40) . "\n");

$cronRuns = $container->resolve(CronJobRunRepository::class);
$runId = $cronRuns->start('process-crawl-jobs');

try {
    $processor = $container->resolve(CrawlJobProcessor::class);
    $result = $processor->processQueued($limit);

    $output = "Processed: {$result['processed']}, Failed: {$result['failed']}";
    fwrite(STDOUT, "Processed: {$result['processed']}\n");
    fwrite(STDOUT, "Failed: {$result['failed']}\n");
    $cronRuns->finish($runId, true, $output);
} catch (\Throwable $e) {
    $cronRuns->finish($runId, false, $e->getMessage());
    fwrite(STDERR, "Crawl processing failed: {$e->getMessage()}\n");
    exit(1);
}

fwrite(STDOUT, str_repeat('-', 40) . "\n");
fwrite(STDOUT, "Done.\n");
