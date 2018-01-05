<?php

namespace Taeram\Bot;

class FuckIt extends \Taeram\Bot {

  /**
   * Initialize the bot
   *
   * @return self
   */
  public function initialize() {
    $baseApiUrl = $this->config['google_cse_url'];
    $apiKey = $this->config['google_cse_token'];
    $userId = $this->config['google_cse_cx_id'];

    $this->bot->hears('/fuckit (.+)', function(\BotMan\BotMan\BotMan $bot, $query) use ($baseApiUrl, $apiKey, $userId) {
      // Make it look like the bot is typing
      $bot->types();

      $apiUrl = $baseApiUrl . '?q=' . urlencode($query) . '&key=' . $apiKey . '&cx=' . $userId . '&searchType=image';
      $request = \Requests::get($apiUrl);
      if ($request->status_code != 200) {
        throw new \Exception("Could not retrieve images from $apiUrl: " . print_r($request, true));
      }

      $results = json_decode($request->body);
      $imageIndex = rand(0, count($results->items) - 1);
      $image = $results->items[$imageIndex];

      $bot->reply($image->link);
    });

    return $this;
  }
}