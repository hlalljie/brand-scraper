<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Services\WebScraper;
use Exception;

class UrlValidationException extends Exception {}



class BrandScraperController extends Controller
{


    private $timeout = 30;
    private $scraper;

    public function __construct()
    {
        $this->scraper = new WebScraper();
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
            $content = $this->scraper->scrapeUrl($url);
        } catch (Exception $e) {
            return response()->json(["error" => $e->getMessage()], $e->getCode());
        }

        //return response
        return response()->json(['message' => 'Hello', "received" => $url, 'content' => $content]);
    }
}
