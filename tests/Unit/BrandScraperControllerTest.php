<?php

use Tests\TestCase;
use App\Http\Controllers\StyleFinderController;
use App\Http\Controllers\UrlValidationException;

uses(TestCase::class);

describe('BrandScraperController@getUrl', function () {
    function callGetUrl($requestData)
    {
        $controller = new StyleFinderController();

        $method = new ReflectionMethod($controller, 'getUrl');
        $method->setAccessible(true);
        return $method->invoke($controller, $requestData);
    }

    // [string input, string expected response]
    $validUrls = [
        ['example.com', 'https://example.com'],
        ['http://example.com', 'http://example.com'],
        ['https://example.com', 'https://example.com'],
        ['example.com/path?param=value', 'https://example.com/path?param=value'],
        ['mail.google.com', 'https://mail.google.com'],
    ];
    // [string input, string expected exception message]
    $invalidUrls = [
        ['not-a-valid-url', 'https://not-a-valid-url is not a valid URL'],
        ['https://localhost', 'https://localhost is not an allowed URL'],
        ['https://127.0.0.1', 'https://127.0.0.1 is not a valid URL'],
        ['https://this-domain-does-not-exist-123456789.com', 'https://this-domain-does-not-exist-123456789.com is not a valid URL'],
        ['', 'No url provided'],
    ];
    // [array response input, string expected exception message]
    $invalidRequestData = [
        [[], 'No data provided'],
        [['test' => 'test'], 'No url data provided'],
    ];

    test('validates valid urls', function ($input, $expected) {
        $result = callGetUrl(['url' => $input]);
        expect($result)->toBe($expected);
    })->with($validUrls);

    test('throws exception for invalid urls', function ($input, $expected) {
        expect(fn() => callGetUrl(['url' => $input]))
            ->toThrow(UrlValidationException::class, $expected);
    })->with($invalidUrls);

    test('throws exception with incorrect data', function ($input, $expected) {
        expect(fn() => callGetUrl($input))
            ->toThrow(UrlValidationException::class, $expected);
    })->with($invalidRequestData);
});

describe('BrandScraperController@combineResults', function () {

    // Sort the arrays to make sure order doesn't matter
    function sortNestedArrays(&$array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                sort($value); // Sort values of nested arrays
                sortNestedArrays($value); // Recursively sort nested arrays
            }
        }
    }
    test('correctly combines two different result data', function () {
        $array1 = [
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
        ];

        $array2 = [
            "colors" =>
            [
                "#fdfdff" => ["div"],
                "#123456" => ["heading"],
            ],
            "fonts" => [
                "Inter" => ["heading", "link"],
            ]
        ];

        $expected = [
            "colors" =>
            [
                "#f0f0f2" => ["background"],
                "#fdfdff" => ["background", "div"],
                "#38488f" => ["a:link", "a:visited"],
                "#123456" => ["heading"],
            ],
            "fonts" => [
                "Inter" => ["heading", "paragraph", "link"],
                "Open Sans" => ["paragraph"],
            ]

        ];

        $controller = new StyleFinderController();
        $method = new ReflectionMethod($controller, 'combineResults');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $array1, $array2);



        // Sort both the result and expected arrays
        sortNestedArrays($result);
        sortNestedArrays($expected);

        expect($expected)->toMatchArray($result);
    });

    test('correctly combines first array with empty list', function () {
        $array1 = [
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
        ];

        $array2 = [];

        $expected = [
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

        ];

        $controller = new StyleFinderController();
        $method = new ReflectionMethod($controller, 'combineResults');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $array1, $array2);

        // Sort both the result and expected arrays
        sortNestedArrays($result);
        sortNestedArrays($expected);

        expect($expected)->toMatchArray($result);
    });

    test('correctly combines empty list with second array', function () {
        $array1 = [];

        $array2 = [
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
        ];

        $expected = [
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

        ];

        $controller = new StyleFinderController();
        $method = new ReflectionMethod($controller, 'combineResults');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $array1, $array2);

        // Sort both the result and expected arrays
        sortNestedArrays($result);
        sortNestedArrays($expected);

        expect($expected)->toMatchArray($result);
    });

    test('correctly combines arrays without making hashmaps', function () {
        $array1 = [
            "colors" => [
                "#333" => [0 => 'screen-reader-text', 2 => 'background'], // Non-sequential keys
                "#eee" => [1 => 'variable', 3 => 'other'],
            ],
        ];

        $array2 = [
            "colors" => [
                "#333" => ['background', 'screen-reader-text'], // Duplicate values, different order
                "#eee" => [0 => 'variable', 'new-value'], // Duplicate 'variable', new value
            ],
        ];

        $expected = [
            "colors" => [
                "#333" => ['screen-reader-text', 'background'], // Order should be consistent
                "#eee" => ['variable', 'other', 'new-value'],
            ],
            "fonts" => [], // Empty array if no fonts are present
        ];

        $controller = new StyleFinderController();
        $method = new ReflectionMethod($controller, 'combineResults');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $array1, $array2);

        // Sort both the result and expected arrays
        sortNestedArrays($result);
        sortNestedArrays($expected);

        expect($expected)->toMatchArray($result);
    });
});
