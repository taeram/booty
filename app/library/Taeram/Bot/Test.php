<?php

namespace Taeram\Bot;

class Test extends \Taeram\Bot {

  /**
   * Initialize the bot
   *
   * @return self
   */
  public function initialize() {
    $this->bot->hears('hello', function(\Mpociot\BotMan\BotMan $bot) {
      // Make it look like the bot is typing
      $bot->types();

      $bot->reply('Hello yourself.');
    });

    return $this;
  }
}