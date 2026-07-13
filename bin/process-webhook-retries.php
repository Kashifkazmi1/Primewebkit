#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Repositories\CronJobRunRepository;
use App\Repositories\WebhookLogRepository;
use App\Repositories\WebhookRepository;
use App\Services\WebhookDispatcherService;

/** @var App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$container = $app->container();

fwrite(STDOUT, "AI Chatbot SaaS — Webhook Retry Processor\n");
fwrite(STDOUT, str_repeat('-', 40) . "\n");

$cronRuns = $container->resolve(CronJobRunRepository::class);
$runId = $cronRuns->start('process-webhook-retries');

try {
    $webhookLogs = $container->resolve(WebhookLogRepository::class);
    $webhooks = $container->resolve(WebhookRepository::class);
    $dispatcher = $container->resolve(WebhookDispatcherService::class);

    $pending = $webhookLogs->pendingRetries(20);
    $retried = 0;

    foreach ($pending as $log) {
        $webhook = $webhooks->find((int) $log['webhook_id']);

        if ($webhook === null || !(bool) $webhook['is_active']) {
            continue;
        }

        $dispatcher->retry($log, $webhook['url'], $webhook['secret']);
        $retried++;
    }

    fwrite(STDOUT, "Retried: {$retried}\n");
    $cronRuns->finish($runId, true, "Retried: {$retried}");
} catch (\Throwable $e) {
    $cronRuns->finish($runId, false, $e->getMessage());
    fwrite(STDERR, "Webhook retry processing failed: {$e->getMessage()}\n");
    exit(1);
}

fwrite(STDOUT, str_repeat('-', 40) . "\n");
fwrite(STDOUT, "Done.\n");
