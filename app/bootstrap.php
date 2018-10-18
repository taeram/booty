<?php

include __DIR__ . '/vendor/autoload.php';

// PHP Configuration
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
date_default_timezone_set('America/Edmonton');

// Globals
define('TMP_PATH', __DIR__ . '/tmp');
$isCli = defined('STDIN');

// Load the Configuration
$yaml = new \Symfony\Component\Yaml\Parser();
$config = $yaml->parse(file_get_contents(__DIR__ . '/config/config.yml'));

$customConfigFile = __DIR__ . '/config/config.custom.yml';
if (file_exists($customConfigFile)) {
  $customConfig = $yaml->parse(file_get_contents($customConfigFile));
  $config = array_replace_recursive($config, $customConfig);
}

// Start the logger
$log = new \Monolog\Logger($config['app']['name']);
$log->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/logs/' . $config['app']['name'] . '.log', \Monolog\Logger::DEBUG));
if ($config['app']['debug'] === FALSE) {
  \Monolog\ErrorHandler::register($log, [], \Monolog\Logger::ERROR);
}

// Setup Doctrine
$emConfig = new \Doctrine\ORM\Configuration();
if ($config['app']['debug'] === TRUE) {
  $emCache = new \Doctrine\Common\Cache\ArrayCache();
  $emConfig->setMetadataCacheImpl($emCache);
}
$emDriverImpl = $emConfig->newDefaultAnnotationDriver([__DIR__ . '/library/Taeram/'], FALSE);
$emConfig->setMetadataDriverImpl($emDriverImpl);
if ($config['app']['debug'] === TRUE) {
  $emConfig->setQueryCacheImpl($emCache);
}
$emConfig->setProxyDir(__DIR__ . '/tmp/doctrine/');
$emConfig->setProxyNamespace('DoctrineProxy');
$emConfig->setAutoGenerateProxyClasses($config['app']['debug']);

$entityManager = \Doctrine\ORM\EntityManager::create([
  'driver' => 'pdo_mysql',
  'host' => $config['database']['host'],
  'dbname' => $config['database']['name'],
  'user' => $config['database']['username'],
  'password' => $config['database']['password'],
  'charset' => 'utf8',
  'driverOptions' => [
    // Ensure we request UTF-8 from the database
    1002 => 'SET NAMES utf8',
  ],
], $emConfig);
\Taeram\Entity::setEntityManager($entityManager);

// Bootstrap the Bot drivers
foreach ($config['app']['bot_drivers'] as $botDriver) {
  $botDriverClass = "BotMan\\Drivers\\$botDriver";
  \BotMan\BotMan\Drivers\DriverManager::loadDriver($botDriverClass);
}

/**
 * Instantiate a bot.
 *
 * @param string $botName The bot name
 * @param array $config The app config
 *
 * @return \Taeram\Bot\$botName
 */
function instantiate_bot($botName, $config) {
  $botClass = "\\Taeram\\Bot\\$botName";
  $botConfig = [
    'telegram' => [
      'token' => $config['bots'][$botName]['telegram_token'],
    ],
    'slack' => [
      'token' => $config['slack']['token'],
    ],
  ];

  return $botClass::factory($botConfig, $config['bots'][$botName])
    ->setDrivers($config['app']['bot_drivers'])
    ->initialize();
}
