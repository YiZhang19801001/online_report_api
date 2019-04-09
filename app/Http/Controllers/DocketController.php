<?php

namespace App\Http\Controllers;

use App\Docket;
use Illuminate\Http\Request;

class DocketController extends Controller
{
    public function index(Request $request)
    {
        $dockets = Docket::take(10)->get();

        return response()->json(compact("dockets"), 200);
    }
}
