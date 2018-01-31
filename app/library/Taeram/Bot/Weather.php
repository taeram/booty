<?php

namespace Taeram\Bot;

class Weather extends \Taeram\Bot
{
    /**
     * How many seconds to cache the site list for.
     *
     * @var int
     */
    protected $cacheSiteListForSeconds = 86400;

    /**
     * Get the list of cities.
     *
     * @return string
     */
    public function getCitiesList()
    {
        // Environment Canada Config
        $xml = $this->getCachedUrl($this->config['ec_sites_url'], $this->cacheSiteListForSeconds);
        $ecSiteList = simplexml_load_string($xml);

        $sites = null;
        foreach ($ecSiteList->site as $site) {
            $site = (array) $site;
            $sites[] = [
        'id' => $site['@attributes']['code'],
        'name' => $site['nameEn'],
        'province' => $site['provinceCode'],
      ];
        }

        return $sites;
    }

    /**
     * Initialize the bot.
     *
     * @return self
     */
    public function initialize()
    {
        $baseApiUrl = $this->config['ec_city_url'];

        $this->bot->hears('/weather (.+)', function (\BotMan\BotMan\BotMan $bot, $city) use ($baseApiUrl) {
            // Make it look like the bot is typing
            $bot->types();

            // Search for the city
            $hasFoundCity = false;
            $sites = $this->getCitiesList();
            foreach ($sites as $site) {
                if (strtolower($site['name']) === strtolower($city)) {
                    $hasFoundCity = true;
                    $apiUrl = $baseApiUrl . '/' . $site['province'] . '/' . $site['id'] . '_e.xml';
                    break;
                }
            }

            if (!$hasFoundCity) {
                return $bot->reply('Could not find city: ' . $city);
            }

            // Retrieve the city's weather
            $request = \Requests::get($apiUrl);
            if ($request->status_code !== 200) {
                $bot->reply('Could not retrieve city weather url');

                throw new \Exception("Could not retrieve city weather url: $apiUrl");
            }

            $weather = simplexml_load_string($request->body);
            $currentConditions = $weather->currentConditions;

            $response = 'Location: ' . $currentConditions->station . "\nTemperature: " . $currentConditions->temperature . "\nCurrently: " . $currentConditions->condition;
            $bot->reply($response);
        });

        return $this;
    }
}
