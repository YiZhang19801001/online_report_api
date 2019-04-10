<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $api_token = $request->bearerToken();

        $user = User::where('api_token', $api_token)->first();

        $shops = $user->shops()->get();

        return response()->json(compact("shops"));
    }
}
