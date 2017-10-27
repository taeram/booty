#!/usr/bin/env php
<?php

// Run the cron for each bot

if ($argc !== 2) {
  echo "Usage: " . basename($argv[0]) . " <always|hourly|daily|weekly|monthly>\n";
  exit(1);
}

$cronType = $argv[1];

require_once __DIR__ . '/bootstrap.php';

foreach ($config['bots'] as $botName => $botConfig) {
  $botClass = "\\Taeram\\Bot\\$botName";
  $botClass::factory([
       'telegram_token' => $config['bots'][$botName]['telegram_token']
      ],
      $config['bots'][$botName]
    )
    ->initialize()
    ->cron($cronType);
}
