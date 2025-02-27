<?php

declare(strict_types=1);

use Temporal\FeatureFlags;
use Temporal\Testing\Environment;
use Temporal\Tests\SearchAttributeTestInvoker;

chdir(__DIR__ . '/../..');
require_once __DIR__ . '/../../vendor/autoload.php';

$environment = Environment::create();
$environment->startTemporalTestServer();
(new SearchAttributeTestInvoker)();
$environment->startRoadRunner('./rr serve -c .rr.silent.yaml -w tests/Functional');
register_shutdown_function(fn() => $environment->stop());

// Default feature flags
FeatureFlags::$warnOnWorkflowUnfinishedHandlers = false;
