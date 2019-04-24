<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $shops = $user->shops()->get();

        return response()->json(compact("shops"));
    }
}
