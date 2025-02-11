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

        // Create progress tracker in database
        $tracker = ProgressTracker::create([
            'progress' => ['time' => 0, 'done' => false]
        ]);

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
            ]
        ];

        dispatch(function () use ($loadTime, $tracker, $testNumber, $testResponses) {
            for ($i = 1; $i <= $loadTime; $i++) {
                $tracker->update(['progress' => ['time' => $i, 'done' => false]]);
                sleep(1);
            }
            $tracker->update(['progress' => ['time' => $i, 'done' => true, 'resultData' => $testResponses[$testNumber]]]);
        });

        return response()->json([
            'tracker' => $tracker->id
        ]);


        // return response()->json($testResponses[$testNumber]);
    }
    public function checkProgress($trackerId)
    {
        $tracker = ProgressTracker::find($trackerId);
        return response()->json([
            'progress' => $tracker->progress
        ]);
    }
}
