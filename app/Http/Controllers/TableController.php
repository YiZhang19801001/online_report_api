<?php

namespace App\Http\Controllers;

use App\Shop;
use App\Table;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class TableController extends Controller
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

        $table_status = $request->input('table_status', null);
        if ($table_status === null) {
            $tables = Table::all();
        } else {
            $tables = Table::where('table_status', $table_status)->get();
        }

        return response()->json(compact('tables'), 200);
    }
}
