<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ProgressTracker;
use App\Services\WebScraper;
use App\Services\OllamaParser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Exception;

defined('SIGTERM') or define('SIGTERM', 15);

class UrlValidationException extends Exception {}



class StyleFinderController extends Controller
{


    private $timeout = 30;
    private $llmTimeout = 600;
    private $chunkLength = 2000;



    private static function getUrl($requestData)
    {
        //Check if any data was sent
        if ($requestData === []) {
            throw new UrlValidationException('No data provided');
        }

        // check if url data was sent
        if (!array_key_exists('url', $requestData)) {
            throw new UrlValidationException('No url data provided');
        }

        // check if url is empty
        if (empty($requestData['url'])) {
            throw new UrlValidationException('No url provided');
        }

        // add https if no http or https present
        $url = $requestData['url'];
        if ($url && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        // dns validate request
        try {
            // Can't use Request validation, need to validate URL manually
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new UrlValidationException($url . " is not a valid URL");
            }
            // Check if domain exists
            if (!checkdnsrr(parse_url($url, PHP_URL_HOST), 'A')) {
                throw new UrlValidationException($url . " domain does not exist");
            }
        } catch (Exception $e) {
            throw new UrlValidationException($url . " is not a valid URL");
        }

        // Block dangerous urls
        $urlData = parse_url($url);
        $host = $urlData['host'] ?? '';
        if ($host === 'localhost' || $host === '127.0.0.1') {
            throw new UrlValidationException($url . ' is not an allowed URL');
        }

        return $url;
    }

    private static function sendError($tracker, $error)
    {
        $tracker->update(['done' => true, 'status' => 'error', 'results' => ["error" => $error->getMessage()]]);
        throw $error;
    }

    public function checkProgress($trackerId)
    {
        $tracker = ProgressTracker::find($trackerId);
        return response()->json($tracker);
    }

    public function stop($processId)
    {

        // Kill worker process
        Log::info("Stopping process: " . $processId);
        posix_kill($processId, SIGTERM);
        Log::info("Process killed");

        // Clear work queue
        Log::info("Clearing queue");
        Artisan::call('queue:clear');
        $queueCount = DB::table('jobs')->count();
        Log::info("Jobs remaining in queue: " . $queueCount);

        // Give the worker a moment to start
        sleep(1);

        // Kill ollama running model
        Log::info("Killing ollama runner");
        $ollamaKillOutput = shell_exec('pkill -f "ollama runner"');
        Log::info("Ollama kill output: " . $ollamaKillOutput);

        // Check if worker started
        Log::info("Starting new worker");
        $workerStart = shell_exec('cd /style-finder && php artisan queue:work > /dev/null 2>&1 &');
        Log::info("Worker start output: " . ($workerStart ?? "no output"));

        return response()->json([
            'success' => true,
            'queueCount' => $queueCount
        ]);
    }

    public function index(Request $request)
    {
        Log::info('Creating db tracker');
        // Create progress tracker in database
        $tracker = ProgressTracker::create();
        Log::info('Finished Creating db tracker');

        $requestData = $request->all();
        $timeout = $this->timeout;
        $llmTimeout = $this->llmTimeout;
        $chunkLength = $this->chunkLength;


        // dispatch job to concurrent job queue
        dispatch(function () use ($requestData, $tracker, $timeout, $llmTimeout, $chunkLength) {

            // get process id and update it in the db
            $pid = getmypid();
            $tracker->update(['process_id' => $pid]);

            $scraper = new WebScraper($timeout);
            $ollamaParser = new OllamaParser($llmTimeout);

            // refresh tracker to prevent additional jobs
            $newTracker = ProgressTracker::find($tracker->id);

            // validate and format url
            try {
                $url = StyleFinderController::getUrl($requestData);
            } catch (UrlValidationException $e) {
                StyleFinderController::sendError($newTracker, $e);
            }

            Log::info($url . " validated");
            $newTracker->update(['status' => 'scraping']);

            // fetch html content
            try {
                $content = $scraper->scrapeUrl($url);
            } catch (Exception $e) {
                StyleFinderController::sendError($newTracker, $e);
            }

            Log::info($url . " parsed");
            // find chunk size

            $contentChunk = substr($content, 0, $chunkLength);
            $totalBatches = ceil(strlen($content) / $chunkLength);
            $completedBatches = 0;

            $newTracker->update(['status' => 'parsing', 'total_batches' => $totalBatches]);

            // parse content
            try {
                $result = $ollamaParser->parse($content, $chunkLength);
            } catch (Exception $e) {
                StyleFinderController::sendError($newTracker, $e);
            }

            Log::info($url . " parsed");
            $newTracker->update(['done' => true, 'results' => ["received" => $url, 'brandData' => $result, "parsedData" => $contentChunk], 'status' => 'done', 'completed_chunks' => $completedBatches]);
        });

        return response()->json(["tracker" => $tracker->id]);



        //return response
        // return response()->json(["received" => $url, 'brandData' => $result, "parsedData" => $contentChunk]);
    }
}
