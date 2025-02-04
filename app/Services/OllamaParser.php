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
        $systemPromptIntro = "You are a helpful AI assistant. You MUST respond with ONLY valid JSON matching exactly this format, with no other text, markdown, or explanation:";
        $jsonFormat = '{"response": "your response here"}';

        $systemPrompt = $systemPromptIntro . "\n" . $jsonFormat;
        $userPrompt = "Please respond with just 'hello world'";

        $response = $this->client->post('/api/generate', [
            'json' => [
                'model' => 'deepseek-r1:1.5b',
                'prompt' => $userPrompt,
                'system' => $systemPrompt,
                'format' => 'json',
                'stream' => false
            ]
        ]);

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        // Get response data f.e. {\"answer\": \"Paris\"}
        $llmResponse = $data['response'];

        // Decode one more time if it's a JSON string so it loses the backslashes
        if (is_string($llmResponse) && json_validate($llmResponse)) {
            $llmResponse = json_decode($llmResponse, true);
        }
        return $llmResponse['response'] ?? $llmResponse;
    }
}
