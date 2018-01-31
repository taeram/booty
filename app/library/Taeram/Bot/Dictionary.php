<?php

namespace Taeram\Bot;

class Dictionary extends \Taeram\Bot
{
    /**
     * Process the message.
     */
    public function initialize()
    {
        $baseApiUrl = $this->config['api_url'];
        $apiKey = $this->config['api_key'];

        $this->bot->hears('/dict (.+)', function (\BotMan\BotMan\BotMan $bot, $query) use ($baseApiUrl, $apiKey) {
            // Make it look like the bot is typing
            $bot->types();

            $apiUrl = $baseApiUrl . '/' . urlencode($query) . '?key=' . $apiKey;
            $request = \Requests::get($apiUrl);
            if ($request->status_code !== 200) {
                $bot->reply('Cannot connect to API');

                throw new \Exception('Cannot connect to API: ' . $apiUrl);
            }
            $body = $request->body;

            if (preg_match('/Invalid API key/i', $body)) {
                $bot->reply("Cannot connect to API: $body");

                throw new \Exception('Cannot connect to API: ' . $apiUrl . '. Error: ' . $body);
            }

            // Tidy up nested xml in the <dt> elements
            if (preg_match_all('/<dt>(.+?)<\/dt>/', $body, $matches)) {
                foreach ($matches[1] as $originalString) {
                    $tidiedString = strip_tags($originalString);
                    $body = str_replace($originalString, $tidiedString, $body);
                }
            }

            $xml = @simplexml_load_string($body);
            if (!$xml) {
                $bot->reply('Cannot parse XML');

                throw new \Exception('Cannot parse XML: ' . $body);
            }

            $results = json_decode(json_encode($xml), $assoc = true);
            if (!isset($results['entry'])) {
                return $bot->reply('Could not find definition');
            }

            if (!isset($results['entry'][0])) {
                $results['entry'] = [$results['entry']];
            }

            $response = '<b>Definition of ' . $query . '</b>' . "\n";
            $counter = 0;
            foreach ($results['entry'] as $entry) {
                if (isset($entry['def'], $entry['def']['dt'])) {
                    if (!is_array($entry['def']['dt'])) {
                        $entry['def']['dt'] = [$entry['def']['dt']];
                    }

                    foreach ($entry['def']['dt'] as $def) {
                        $def = trim($def);
                        $def = preg_replace('/^:/', '', $def);

                        $counter++;
                        $response .= "<b>$counter</b>: " . $def . "\n";
                    }
                }
            }

            $bot->reply($response, ['parse_mode' => 'HTML']);
        });

        return $this;
    }
}
