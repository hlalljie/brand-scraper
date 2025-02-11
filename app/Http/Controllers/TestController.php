<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProgressTracker;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;


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
        $tracker = ProgressTracker::create();
        Log::info('Finished Creating db tracker');

        // dispatch job to concurrent job queue
        dispatch(function () use ($loadTime, $tracker, $testNumber, $testResponses) {

            // refresh tracker to prevent additional jobs
            $newTracker = ProgressTracker::find($tracker->id);
            // sleep over time and update
            sleep($loadTime / 3);
            // simulate scraping task
            $newTracker->update(['status' => 'scraping']);
            sleep($loadTime / 3);
            // simulate parsing partial update
            $newTracker->update(['results' => $testResponses[2], 'status' => 'parsing']);
            sleep($loadTime / 3);
            // wrap up at the end
            $newTracker->update(['done' => true, 'results' => $testResponses[$testNumber], 'status' => 'done']);
        });

        return response()->json([
            'tracker' => $tracker->id
        ]);


        // return response()->json($testResponses[$testNumber]);
    }
    public function checkProgress($trackerId)
    {
        $tracker = ProgressTracker::find($trackerId);
        return response()->json($tracker);
    }
}
