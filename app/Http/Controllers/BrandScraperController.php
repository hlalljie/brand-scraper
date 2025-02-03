<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; 

class BrandScraperController extends Controller
{
    public function index(Request $request ){
        Log::info($request->all());
        $website = $request->website;
        return response()->json(['message' => 'Hello', "received" => $website]);
    }
}
