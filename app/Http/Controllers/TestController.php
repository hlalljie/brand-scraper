<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        if ($loadTime > 0) {
            sleep($loadTime);
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
        ];
        return response()->json($testResponses[0]);
    }
}
