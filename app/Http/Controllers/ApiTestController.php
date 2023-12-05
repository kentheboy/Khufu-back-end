<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiTestController extends Controller
{
    public function test(Request $request) {
        $response = [
            'msg' => "test api request successfully made",
            'requestContent' => $request
        ];

        return response($response);
    }
}
