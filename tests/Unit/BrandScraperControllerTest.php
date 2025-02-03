<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\BrandScraperController;
use App\Http\Controllers\UrlValidationException;

uses(TestCase::class);

describe('BrandScraperController@getUrl', function () {
    function callGetUrl($requestData)
    {
        $controller = new BrandScraperController();
        $request = Request::create('/', 'POST', $requestData);

        // This will give us a proper request with validation
        app()->instance('request', $request);

        $method = new ReflectionMethod($controller, 'getUrl');
        $method->setAccessible(true);
        return $method->invoke($controller, $request);
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
        ['not-a-valid-url', 'URL not valid'],
        ['https://localhost', 'URL not allowed'],
        ['https://127.0.0.1', 'URL not valid'],
        ['https://this-domain-does-not-exist-123456789.com', 'URL not valid'],
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
