<?php

namespace App\Http\Controllers;

use App\Docket;
use App\Shop;
use Illuminate\Http\Request;

class DocketController extends Controller
{
    public function index(Request $request)
    {
        $shop_id = $request->input("shop_id", 1);

        $shop = Shop::find($shop_id);

        \Config::set('database.connections.sqlsrv.host', $shop->database_ip);

        $dockets = Docket::take(10)->get();

        return response()->json(compact("dockets"), 200);
    }
}
