<?php

namespace App\Http\Controllers;

use App\Shop;
use App\Site;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // find shop according to inputs shop_ip
        $shopId = isset($request->shopId) ? $request->shopId : $user->shops()->first()->shop_id;
        $check_if_shop_belong_to_user = $user->shops()->where('shops.shop_id', $shopId)->first();
        if ($check_if_shop_belong_to_user === null) {
            return response()->json(['errors' => ['Not authorized account to view this shop']], 400);
        }

        $shop = Shop::find($shopId);

        DB::purge();

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
        \Config::set('database.connections.sqlsrv.username', $shop->username);
        \Config::set('database.connections.sqlsrv.password', $shop->password);
        \Config::set('database.connections.sqlsrv.database', $shop->database_name);
        \Config::set('database.connections.sqlsrv.port', $shop->port);

        $sites = Site::where('site_id', '>=', 0)->select('site_id', 'site_code', 'site_name')->get();

        return response()->json(compact('sites'), 200);
    }
}
