<?php

namespace App\Services;


use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

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

    public function parse(string $websiteData, int $chunkLength = 10000)
    {

        // set up user prompt
        $userPrompt = substr($websiteData, 0, $chunkLength);

        // Set up system prompt
        $systemPromptIntro = "You are a helpful AI assistant. You MUST respond with ONLY valid JSON matching exactly this format, with no other text, markdown, or explanation. The user will give you website data and you will look through it for any fonts or colors. You will respond the the different colors and where they appear. The possible locations for colors are: [background, heading, paragraph, svg, border, button, link, variable, other]. The possible locations for fonts are: [heading, paragraph, button, link, variable, other]. You will respond following the JSON format below, on the top level there should only be two keys, 'colors' and 'fonts'. Colors should include different hex code, rbg, values, or hsla values. Fonts should include different font names.(note that all the colors and fonts in the format example are placeholders):";
        // giv structural data
        $jsonFormat = '{"colors": {"#000000": ["background", "heading"], "#123456":["other"]}, "fonts": {"Inter": ["heading", "paragraph"], "Arial": ["button"], "Times New Roman": ["heading", "other"]}}';
        $systemPrompt = $systemPromptIntro . "\n" . $jsonFormat;

        // send request to llm
        $response = $this->client->post('/api/generate', [
            'json' => [
                'model' => 'deepseek-r1:1.5b',
                'prompt' => $userPrompt,
                'system' => $systemPrompt,
                'format' => 'json',
                'stream' => false
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
