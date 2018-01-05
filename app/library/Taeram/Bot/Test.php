<?php

namespace Taeram\Bot;

class Test extends \Taeram\Bot {

  /**
   * Initialize the bot
   *
   * @return self
   */
  public function initialize() {
    $this->bot->hears('hello', function(\BotMan\BotMan\BotMan $bot) {
      $bot->types();
      $bot->reply('Hello yourself.');
    });

    return $this;
  }
}