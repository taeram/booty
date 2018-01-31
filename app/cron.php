#!/usr/bin/env php
<?php

// Run the cron for each bot

if ($argc !== 2) {
    echo 'Usage: ' . basename($argv[0]) . " <always|hourly|daily|weekly|monthly>\n";
    exit(1);
}

$cronType = $argv[1];

require_once __DIR__ . '/bootstrap.php';

foreach ($config['bots'] as $botName => $botConfig) {
    instantiate_bot($botName, $config)
    ->cron($cronType);
}
