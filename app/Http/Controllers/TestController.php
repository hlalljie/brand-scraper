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
            ]
        ];
        return response()->json($testResponses[0]);
    }
}
