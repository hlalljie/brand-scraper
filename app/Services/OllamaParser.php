<?php

namespace App\Services;


use GuzzleHttp\Client;
use DOMDocument;
use DOMElement;
use Exception;

class OllamaParser
{
    private Client $client;
    private int $timeout;

    public function __construct(int $timeout = 600)
    {
        $this->timeout = $timeout;
        $this->client = new Client([
            'base_uri' => 'http://localhost:11434',
            'timeout' => $this->timeout,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; BrandBot/1.0)',
                'Accept' => 'text/html,text/css,application/javascript',
            ]
        ]);
    }

    public function parse(string $websiteData)
    {
        $prompt = "Please respond back with 'hello world from deepseek' if you understand";
        $response = $this->client->post('/api/generate', [
            'json' => [
                'model' => 'deepseek-r1:8b',
                'prompt' => $prompt,
                'stream' => false
            ]
        ]);
        return $response;
    }
}
