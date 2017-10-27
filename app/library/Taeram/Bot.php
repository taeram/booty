<?php

namespace Taeram;

abstract class Bot extends \Taeram\Entity {

  /**
   * The bot
   *
   * @var \Mpociot\BotMan\BotMan
   */
  protected $bot;

  /**
   * The bot config
   *
   * @var array
   */
  protected $config;

  /**
   * Cron constants
   */
  const CRON_ALWAYS = 'always';
  const CRON_HOURLY = 'hourly';
  const CRON_DAILY = 'daily';
  const CRON_WEEKLY = 'weekly';
  const CRON_MONTHLY = 'monthly';

  /**
   * Construct the bot
   *
   * @param array $config The config
   *
   * @return self
   */
  public static function factory($botmanConfig, $botConfig) {
    // create an instance
    $bot = \Mpociot\BotMan\BotManFactory::create($botmanConfig);

    return new static($bot, $botConfig);
  }

  /**
   * Create the bot
   *
   * @param \Mpociot\BotMan\BotMan $bot
   */
  public function __construct(\Mpociot\BotMan\BotMan $bot, $config) {
    $this->bot = $bot;
    $this->config = $config;
  }

  /**
   * Getters and Setters
   */

  /**
   * Get the bot
   *
   * @return \Mpociot\BotMan\BotMan
   */
  public function getBot() {
    return $this->bot;
  }

  /**
   * Get the config
   *
   * @return array
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Botman Functions
   */

  /**
   * Initialize the bot
   *
   * @return self
   */
  abstract public function initialize();

  /**
   * Listen for incoming messages
   */
  public function listen() {
    return $this->bot->listen();
  }

  /**
   * Utilities
   */

  /**
   * Run a cron job
   *
   * @return self
   */
  public function cron($cronType) {
    return $this;
  }

  /**
   * Get an URL and cache it for a number of seconds
   *
   * @param string $url The URL to retrieve
   * @param integer $secondsToCache The number of seconds to cache the results for
   *
   * @return string
   */
  public function getCachedUrl($url, $secondsToCache) {
    // Create the cache dir if it doesn't exist
    if (!file_exists(TMP_PATH . '/cache')) {
      mkdir(TMP_PATH . '/cache', 0775, true);
    }

    // Download and cache the url
    $cacheFile = TMP_PATH . '/cache/' . md5($url);
    if (!file_exists($cacheFile) || filemtime($cacheFile) < time() - $secondsToCache) {
      $request = \Requests::get($url);
      if ($request->status_code != 200) {
        throw new \Exception(__CLASS__ . ':' . __FUNCTION__ . ': Cannot download ' . $url);
      }
      file_put_contents($cacheFile, $request->body);
    }

    return file_get_contents($cacheFile);
  }
}
