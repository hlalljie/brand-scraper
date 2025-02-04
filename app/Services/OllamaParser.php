<?php

namespace App\Services;


use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Dotenv\Dotenv;


class OllamaParser
{
    private Client $client;
    private int $timeout;
    private $model;

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
        // Load env variable for model
        $dotenv = Dotenv::createImmutable(__DIR__, '.env.build');
        $dotenv->load();
        $this->model = env('OLLAMA_MODEL');
    }

    public function parse(string $websiteData, int $chunkLength = 10000)
    {

        // test smaller input
        $testData = "body { background-color: #000000; font-family: Arial; }";
        $websiteData = $testData;

        // set up user prompt
        $userPrompt = "Analyze this website data for colors and fonts:\n\n" . substr($websiteData, 0, $chunkLength);


        // Set up system prompt
        $systemPromptIntro = "You are a helpful AI assistant that MUST respond with ONLY valid JSON. Your response MUST contain exactly two top-level keys: 'colors' and 'fonts'. No other keys are allowed. No additional text or explanations.

For any website data provided:
- Extract all colors (hex, rgb, hsla) and their locations
- Extract all font names and their locations

Valid locations for colors: [background, heading, paragraph, svg, border, button, link, variable, other]
Valid locations for fonts: [heading, paragraph, button, link, variable, other]

Your response MUST match this exact structure (note that the structure contains placeholder data for colors and fonts and should not be copied exactly):";
        // giv structural data
        $jsonFormat = '{"colors": {"#000000": ["background", "heading"], "#123456":["other"]}, "fonts": {"Inter": ["heading", "paragraph"], "Arial": ["button"], "Times New Roman": ["heading", "other"]}}';
        $systemPrompt = $systemPromptIntro . "\n" . $jsonFormat;

        // send request to llm
        $response = $this->client->post('/api/generate', [
            'json' => [
                'model' => $this->model,
                'prompt' => $userPrompt,
                'system' => $systemPrompt,
                'format' => 'json',
                'stream' => false,
                'temperature' => 0.1,  // Lower temperature for more consistent outputs
                'top_p' => 0.1,       // Lower top_p for more focused responses
                'timeout' => $this->timeout,
                'repetition_penalty' => 1.2
            ]
        ]);

        // Get response data f.e. {\"answer\": \"Paris\"}
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        $llmResponse = $data['response'];

        // Decode one more time if it's a JSON string so it loses the backslashes
        if (is_string($llmResponse) && json_validate($llmResponse)) {
            $llmResponse = json_decode($llmResponse, true);
        }
        Log::info($data);
        return $llmResponse['response'] ?? $llmResponse;
    }
}
