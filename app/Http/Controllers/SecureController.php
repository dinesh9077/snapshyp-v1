<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class SecureController extends Controller
{
    public function verifySessionToken(Request $request)
    {
        return response()->json([
            'success' => true,
            'shop' => $request->shop,
            'payload' => $request->payload
        ]); 
    }
}
