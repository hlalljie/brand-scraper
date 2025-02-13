<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProgressTracker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Exception;

defined('SIGTERM') or define('SIGTERM', 15);



class TestController extends Controller
{
    public function index(Request $request)
    {

        //Check if any data was sent
        if ($request->all() === []) {
            throw new Exception('No data provided');
        }

        $testNumber = 0;
        $loadTime = 0;

        // load test number
        if (array_key_exists('testNumber', $request->all())) {
            $testNumber = $request->input('testNumber');
        }

        // load load time
        if (array_key_exists('loadTime', $request->all())) {
            $loadTime = $request->input('loadTime');
        }

        $testResponses = [
            [
                "received" => "example.com",
                'brandData' =>
                [
                    "colors" =>
                    [
                        "#f0f0f2" => ["background"],
                        "#fdfdff" => ["background", "div"],
                        "#38488f" => ["a:link", "a:visited"],
                    ],
                    "fonts" => [
                        "Inter" => ["heading", "paragraph"],
                        "Open Sans" => ["paragraph"],
                    ]
                ],
                "parsedData" => 'fake parsed data',
            ],
            [
                "received" => "badurl",
                "error" => "badurl is not a valid URL"
            ],
            [
                "received" => "example.com",
                'brandData' =>
                [
                    "colors" =>
                    [
                        "#fdfdff" => ["div"],
                        "#38488f" => ["a:link"],
                    ],
                    "fonts" => [
                        "Inter" => ["heading"],
                    ]
                ],
                "parsedData" => 'fake parsed data',
            ],
        ];
        Log::info('Creating db tracker');
        // Create progress tracker in database
        $tracker = ProgressTracker::create(['status' => 'scraping']);
        Log::info('Finished Creating db tracker');

        // dispatch job to concurrent job queue
        dispatch(function () use ($loadTime, $tracker, $testNumber, $testResponses) {
            // get process id and update it in the db
            $pid = getmypid();
            $tracker->update(['process_id' => $pid]);

            // refresh tracker to prevent additional jobs
            $newTracker = ProgressTracker::find($tracker->id);
            // sleep over time and update
            sleep($loadTime / 3);
            // simulate start parsing task
            $newTracker->update(['status' => 'parsing', 'total_batches' => 2]);
            sleep($loadTime / 3);
            // simulate parsing partial update
            $newTracker->update(['results' => $testResponses[2], 'status' => 'parsing', 'completed_batches' => 1]);
            sleep($loadTime / 3);
            // wrap up at the end
            $newTracker->update(['done' => true, 'results' => $testResponses[$testNumber], 'status' => 'done', 'completed_batches' => 2]);
        });

        return response()->json([
            'tracker' => $tracker->id
        ]);
    }

    public function checkProgress($trackerId)
    {
        $tracker = ProgressTracker::find($trackerId);
        return response()->json($tracker);
    }

    public function stop($processId)
    {
        Log::info("Stopping process: " . $processId);

        posix_kill($processId, SIGTERM);
        Log::info("Process killed");

        Log::info("Clearing queue");
        Artisan::call('queue:clear');
        $queueCount = DB::table('jobs')->count();
        Log::info("Jobs remaining in queue: " . $queueCount);

        // Give the worker a moment to start
        sleep(1);

        // Check if worker started
        Log::info("Starting new worker");
        $workerStart = shell_exec('cd /style-finder && php artisan queue:work > /dev/null 2>&1 &');
        Log::info("Worker start output: " . ($workerStart ?? "no output"));

        return response()->json([
            'success' => true,
            'queueCount' => $queueCount
        ]);
    }
}
