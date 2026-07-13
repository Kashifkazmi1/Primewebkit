#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Repositories\CronJobRunRepository;
use App\Services\SubscriptionService;

/** @var App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$container = $app->container();

fwrite(STDOUT, "AI Chatbot SaaS — Billing Cycle Processor\n");
fwrite(STDOUT, str_repeat('-', 40) . "\n");

$cronRuns = $container->resolve(CronJobRunRepository::class);
$runId = $cronRuns->start('process-billing-cycle');

try {
    $subscriptions = $container->resolve(SubscriptionService::class);

    $trialsExpired = $subscriptions->processExpiredTrials();
    $renewalResult = $subscriptions->processRenewals();

    $output = sprintf(
        'Trials expired: %d, Renewed: %d, Canceled at period end: %d, Expired past-due: %d',
        $trialsExpired,
        $renewalResult['renewed'],
        $renewalResult['canceled'],
        $renewalResult['past_due']
    );

    fwrite(STDOUT, $output . "\n");
    $cronRuns->finish($runId, true, $output);
} catch (\Throwable $e) {
    $cronRuns->finish($runId, false, $e->getMessage());
    fwrite(STDERR, "Billing cycle processing failed: {$e->getMessage()}\n");
    exit(1);
}

fwrite(STDOUT, str_repeat('-', 40) . "\n");
fwrite(STDOUT, "Done.\n");
