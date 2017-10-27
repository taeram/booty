<?php

namespace Taeram\Bot;

class Morbo extends \Taeram\Bot {

  /**
   * Initialize the bot
   *
   * @return self
   */
  public function initialize() {
    $baseApiUrl = $this->config['api_url'];
    $baseImageUrl = $this->config['image_url'];

    $this->bot->hears('/morbo (.+)', function(\Mpociot\BotMan\BotMan $bot, $query) use ($baseApiUrl, $baseImageUrl) {
      // Make it look like the bot is typing
      $bot->types();

      $apiUrl = $baseApiUrl . '?q=' . urlencode($query);
      $request = \Requests::get($apiUrl);
      if ($request->status_code != 200) {
        $bot->reply("Could not retrieve images from API");
        throw new \Exception("Could not retrieve images from API $apiUrl");
      }

      $results = json_decode($request->body);
      if (count($results) == 0) {
        return $bot->reply("No results found");
      }

      $numResultsToUse = floor(count($results) / 5 + 1);
      $imageIndex = rand(0, $numResultsToUse);
      $image = $results[$imageIndex];
      $imageUrl = $baseImageUrl . '/' . $image->Episode . '/' . $image->Timestamp . '.jpg';

      $bot->reply($imageUrl);
    });

    return $this;
  }
}
