<?php

namespace App\Services;


use GuzzleHttp\Client;
use DOMDocument;
use DOMElement;
use Exception;

class WebScraper
{

    private $client;
    private $timeout;

    public function __construct(int $timeout = 30)
    {
        $this->timeout = $timeout;
        $this->client = new Client([
            'timeout' => $this->timeout,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; BrandBot/1.0)',
                'Accept' => 'text/html,text/css,application/javascript',
            ]
        ]);
    }
    private function getHTML($url)
    {
        try {
            // Get initial HTML
            $html = $this->client->get($url)->getBody();

            // Basic HTML parsing
            $dom = new DOMDocument();
            @$dom->loadHTML($html, LIBXML_NOERROR);

            return $dom;
        } catch (Exception $e) {
            throw new Exception("Error fetching HTML: " . $e->getMessage());
        }
    }

    private function parseHTML($dom)
    {
        $allContent = [];

        // Get all <link> elements, look for any css or stylesheet files
        $linkElements = $dom->getElementsByTagName('link');
        foreach ($linkElements as $link) {
            if ($link instanceof DOMElement) {  // Add this check
                $rel = $link->getAttribute('href');
                if ($rel && (str_contains($rel, '.css') || str_contains($rel, 'stylesheet'))) {
                    try {
                        $allContent[] = $this->client->get($rel)->getBody();
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

        return $allContent;
    }

    private function parseScripts($dom)
    {
        $allContent = [];

        // parse wordpress scripts
        $allContent = array_merge($allContent, $this->parseWordpressScripts($dom));

        return $allContent;
    }

    private function parseWordpressScripts($dom)
    {
        $allContent = [];
        // get style related script data for wordpress sites
        $scriptElements = $dom->getElementsByTagName('script');
        foreach ($scriptElements as $script) {
            if ($script instanceof DOMElement && $script->hasAttribute('src')) {
                $src = $script->getAttribute('src');
                try {
                    $content = $this->client->get($src)->getBody();
                    $styleInfo = $this->findStyleContext($content);
                    if (!empty($styleInfo)) {
                        $allContent[] = implode("\n", array_merge($styleInfo['fonts'] ?? [], $styleInfo['colors'] ?? []));
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        return $allContent;
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

    public function scrapeUrl(string $url): string
    {
        try {
            //get html data as a Document
            $dom = $this->getHTML($url);

            $allContent = [];

            // parse standard HTML data
            $allContent = array_merge($allContent, $this->parseHTML($dom));

            //parse scripts
            $allContent = array_merge($allContent, $this->parseScripts($dom));

            // Join all content with newlines between each piece
            // return implode("\n\n", $allContent);
            return implode("\n\n", $allContent);
        } catch (Exception $e) {
            throw new Exception('Error parsing HTML: ' . $e->getMessage());
        }
    }
}
