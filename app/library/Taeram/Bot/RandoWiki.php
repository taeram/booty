<?php

namespace Taeram\Bot;

class RandoWiki extends \Taeram\Bot
{
    /**
     * The number of results to return.
     *
     * @var int
     */
    protected $numResults = 20;

    /**
     * Ignore pages with these URL prefixes.
     *
     * @var array
     */
    protected $ignoredPagePrefixes = [
    'Main_Page',
    'Special:',
    '-',
    '404.php',
  ];

    /**
     * Ignore these specific pages by URL.
     *
     * @var array
     */
    protected $ignoredPages = [
    'XHamster',
  ];

    /**
     * Initialize the bot.
     *
     * @return self
     */
    public function initialize()
    {
        return $this;
    }

    /**
     * Run the cron job.
     */
    public function cron($cronType)
    {
        if ($cronType !== self::CRON_DAILY) {
            return;
        }

        // Load the page counts
        $url = $this->config['pageviews_url'] . '/' . date('Y/m/d', strtotime('yesterday'));
        $request = \Requests::get($url);
        if ($request->status_code !== 200) {
            throw new \Exception('Cannot download ' . $url);
        }

        $pages = json_decode($request->body, $assoc = true);
        if (!isset($pages['items']) || !isset($pages['items'][0]) || !isset($pages['items'][0]['articles'])) {
            throw new \Exception('invalid JSON from ' . $url);
        }

        $counter = 0;
        $message = 'Top Wikipedia Pages for ' . date('F j, Y') . "\n";
        foreach ($pages['items'][0]['articles'] as $i => $article) {
            if ($counter === $this->numResults) {
                break;
            }

            foreach ($this->ignoredPagePrefixes as $prefix) {
                if (preg_match('/^' . $prefix . '/', $article['article'])) {
                    continue 2;
                }
            }

            foreach ($this->ignoredPages as $page) {
                if (preg_match('/' . $page . '$/', $article['article'])) {
                    continue 2;
                }
            }

            $counter++;
            $title = str_replace('_', ' ', $article['article']);
            $message .= '    <b>' . $counter . '</b>: <a href="https://en.wikipedia.org/wiki/' . urlencode($article['article']) . '">' . $title . '</a> (' . number_format($article['views']) . ' hits)' . "\n";
        }

        $chatIds = $this->config['telegram_chat_id'];
        if (!is_array($chatIds)) {
            $chatIds = [$chatIds];
        }

        $drivers = $this->getDrivers();
        foreach ($drivers as $driverName) {
            foreach ($chatIds as $chatId) {
                $this->bot->say($message, $chatId, "BotMan\\Drivers\\Telegram\\$driverName", ['parse_mode' => 'HTML']);
            }
        }

        return $this;
    }
}
