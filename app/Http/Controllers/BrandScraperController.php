<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PharIo\Manifest\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use DOMDocument;
use DOMElement;
use Exception;

class UrlValidationException extends Exception {}



class BrandScraperController extends Controller
{

    private $client;
    private $timeout = 30;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => $this->timeout,
            'verify' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; BrandBot/1.0; +http://example.com/bot)',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en-US,en;q=0.9',
            ]
        ]);
    }

    private function getUrl(Request $request)
    {
        //Check if any data was sent
        if ($request->all() === []) {
            throw new UrlValidationException('No data provided');
        }

        // check if url data was sent
        if (!array_key_exists('url', $request->all())) {
            throw new UrlValidationException('No url data provided');
        }

        // check if url is empty
        if (empty($request->input('url'))) {
            throw new UrlValidationException('No url provided');
        }

        // add https if no http or https present
        $url = $request->input('url');
        if ($url && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        // dns validate request
        try {
            $validatedUrl = $request->merge(['url' => $url])->validate([
                'url' => 'required|url|active_url'
            ]);
        } catch (ValidationException $e) {
            throw new UrlValidationException("URL not valid");
        }

        // Block dangerous urls
        $urlData = parse_url($validatedUrl["url"]);
        $host = $urlData['host'] ?? '';
        if ($host === 'localhost' || $host === '127.0.0.1') {
            throw new UrlValidationException('URL not allowed');
        }

        // return validated url
        return $validatedUrl['url'];
    }

    private function findStyleContext($content)
    {
        $styleInfo = [];

        // Font patterns - looking for font-family, font declarations, typography
        $fontPatterns = [
            '/font-family:\s*[^;]+;/i',
            '/font:\s*[^;]+;/i',
            '/typography[^}]+}/i',
            '/@font-face\s*{[^}]+}/i',
            '/--[\w-]*font[\w-]*:\s*[^;]+;/i'  // CSS custom properties for fonts
        ];

        // Color patterns - all color formats
        $colorPatterns = [
            '/#[0-9A-Fa-f]{3,6}\b/',          // hex
            '/rgb\([^)]+\)/',                  // rgb
            '/rgba\([^)]+\)/',                 // rgba
            '/hsl\([^)]+\)/',                  // hsl
            '/hsla\([^)]+\)/',                 // hsla
            '/--[\w-]*color[\w-]*:\s*[^;]+;/i' // CSS custom properties for colors
        ];

        // Get extended context (500 chars before and after) for each match
        foreach ($fontPatterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $pos = $match[1];
                    $start = max(0, $pos - 500);
                    $length = min(strlen($content) - $start, $pos - $start + strlen($match[0]) + 500);
                    $styleInfo['fonts'][] = substr($content, $start, $length);
                }
            }
        }

        foreach ($colorPatterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $pos = $match[1];
                    $start = max(0, $pos - 500);
                    $length = min(strlen($content) - $start, $pos - $start + strlen($match[0]) + 500);
                    $styleInfo['colors'][] = substr($content, $start, $length);
                }
            }
        }

        return $styleInfo;
    }
    private function fetchContent(string $url): string
    {
        $client = new Client([
            'timeout' => $this->timeout,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; BrandBot/1.0)',
                'Accept' => 'text/html,text/css,application/javascript',
            ]
        ]);

        try {
            // Get initial HTML
            $html = $client->get($url)->getBody();

            // Basic HTML parsing
            $dom = new DOMDocument();
            @$dom->loadHTML($html, LIBXML_NOERROR);

            $allContent = [];


            // Get all <link> elements, look for any css or stylesheet files
            $linkElements = $dom->getElementsByTagName('link');
            foreach ($linkElements as $link) {
                if ($link instanceof DOMElement) {  // Add this check
                    $rel = $link->getAttribute('href');
                    if ($rel && (str_contains($rel, '.css') || str_contains($rel, 'stylesheet'))) {
                        try {
                            $allContent[] = $client->get($rel)->getBody();
                        } catch (Exception $e) {
                            continue;
                        }
                    }
                }
            }

            // get all <style> elements
            $styleElements = $dom->getElementsByTagName('style');
            foreach ($styleElements as $style) {
                if ($style instanceof DOMElement) {  // Add this check
                    $allContent[] = $style->textContent;
                }
            }

            // get all elements with inline styles
            $elements = $dom->getElementsByTagName('*');
            foreach ($elements as $element) {
                if ($element instanceof DOMElement && $element->hasAttribute('style')) {
                    $allContent[] = '<' . $element->tagName . ' style="' . $element->getAttribute('style') . '">';
                }
            }

            // Get SVG colors
            $svgElements = $dom->getElementsByTagName('svg');
            $svgColors = [];
            foreach ($svgElements as $svg) {
                if ($svg instanceof DOMElement) {
                    // Get fill from SVG itself
                    $fill = $svg->getAttribute('fill');
                    if ($fill && $fill !== 'none') {
                        $svgColors[] = $fill;
                    }

                    // Get fills from path elements inside SVG
                    $paths = $svg->getElementsByTagName('path');
                    foreach ($paths as $path) {
                        if ($path instanceof DOMElement) {
                            $pathFill = $path->getAttribute('fill');
                            if ($pathFill && $pathFill !== 'none') {
                                $svgColors[] = $pathFill;
                            }
                        }
                    }
                }
            }

            if (!empty($svgColors)) {
                $allContent[] = implode("\n", array_unique($svgColors));
            }

            // get style related script data for wordpress sites
            $scriptElements = $dom->getElementsByTagName('script');
            foreach ($scriptElements as $script) {
                if ($script instanceof DOMElement && $script->hasAttribute('src')) {
                    $src = $script->getAttribute('src');
                    try {
                        $content = $client->get($src)->getBody();
                        $styleInfo = $this->findStyleContext($content);
                        if (!empty($styleInfo)) {
                            $allContent[] = implode("\n", array_merge($styleInfo['fonts'] ?? [], $styleInfo['colors'] ?? []));
                        }
                    } catch (Exception $e) {
                        continue;
                    }
                }
            }




            // Join all content with newlines between each piece
            // return implode("\n\n", $allContent);
            return implode("\n\n", $allContent);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch content: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {

        // validate and format url
        try {
            $url = $this->getUrl($request);
        } catch (UrlValidationException $e) {
            return response()->json(["error" => $e->getMessage()], 422);
        }

        Log::info($url . " validated");

        // fetch html content
        try {
            $content = $this->fetchContent($url);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], $e->getCode());
        }

        //return response
        return response()->json(['message' => 'Hello', "received" => $url, 'content' => $content]);
    }
}
