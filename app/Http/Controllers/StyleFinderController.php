<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\ProgressTracker;
use App\Services\WebScraper;
use App\Services\OllamaParser;
use Exception;

class UrlValidationException extends Exception {}



class StyleFinderController extends Controller
{


    private $timeout = 30;
    private $llmTimeout = 600;
    private $chunkLength = 2000;
    private $scraper;
    private $ollamaParser;

    public function __construct()
    {
        $this->scraper = new WebScraper($this->timeout);
        $this->ollamaParser = new OllamaParser($this->llmTimeout);
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
            throw new UrlValidationException($url . " is not a valid URL");
        }

        // Block dangerous urls
        $urlData = parse_url($validatedUrl["url"]);
        $host = $urlData['host'] ?? '';
        if ($host === 'localhost' || $host === '127.0.0.1') {
            throw new UrlValidationException($url . ' is not an allowed URL');
        }

        // return validated url
        return $validatedUrl['url'];
    }

    public function index(Request $request)
    {
        Log::info('Creating db tracker');
        // Create progress tracker in database
        $tracker = ProgressTracker::create();
        Log::info('Finished Creating db tracker');

        // dispatch job to concurrent job queue
        dispatch(function () use ($request, $tracker) {

            // refresh tracker to prevent additional jobs
            $newTracker = ProgressTracker::find($tracker->id);

            // validate and format url
            try {
                $url = $this->getUrl($request);
            } catch (UrlValidationException $e) {
                $this->sendError($newTracker, $e);
            }

            Log::info($url . " validated");
            $newTracker->update(['status' => 'scraping']);

            // fetch html content
            try {
                $content = $this->scraper->scrapeUrl($url);
            } catch (Exception $e) {
                $this->sendError($newTracker, $e);
            }

            Log::info($url . " parsed");
            // find chunk size

            $contentChunk = substr($content, 0, $this->chunkLength);
            $totalChunks = ceil(strlen($content) / $this->chunkLength);
            $completedChunks = 0;

            $newTracker->update(['status' => 'parsing', 'chunks' => $totalChunks]);

            // parse content
            try {
                $result = $this->ollamaParser->parse($content, $this->chunkLength);
            } catch (Exception $e) {
                return response()->json(["error" => $e->getMessage()]);
            }

            Log::info($url . " parsed");
            $newTracker->update(['done' => true, 'results' => $result, 'status' => 'done', 'completed_chunks' => $completedChunks]);
        });

        return response()->json(["tracker" => $tracker->id]);



        //return response
        // return response()->json(["received" => $url, 'brandData' => $result, "parsedData" => $contentChunk]);
    }

    private function sendError($tracker, $error)
    {
        $tracker->update(['done' => true, 'status' => 'error', 'results' => ["error" => $error->getMessage()]]);
        throw $error;
    }

    public function checkProgress($trackerId)
    {
        $tracker = ProgressTracker::find($trackerId);
        return response()->json($tracker);
    }
}
