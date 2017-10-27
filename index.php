<?php

require_once __DIR__ . '/app/bootstrap.php';

if (defined('STDIN')) {
  $botName = $argv[1];
} else if (isset($_GET['bot'])) {
  $botName = $_GET['bot'];
} else {
  header("HTTP/1.0 404 Not Found");
  echo "<h1>404 Not Found</h1>";
  die();
}

if (!isset($config['bots'][$botName])) {
  $log->addError("Unknown bot: $botName");
  exit(1);
}

$botClass = "\\Taeram\\Bot\\$botName";
$botClass::factory([
      'telegram_token' => $config['bots'][$botName]['telegram_token']
    ],
    $config['bots'][$botName]
  )
  ->initialize()
  ->listen();
