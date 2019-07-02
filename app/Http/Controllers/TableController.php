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
        \Config::set('database.connections.sqlsrv.username', $shop->username);
        \Config::set('database.connections.sqlsrv.password', $shop->password);
        \Config::set('database.connections.sqlsrv.database', $shop->database_name);
        \Config::set('database.connections.sqlsrv.port', $shop->port);

        $table_status = $request->input('table_status', null);
        $site_id = $request->input('site_id', null);
        switch ($table_status) {
            case "0":
                if ($site_id !== null) {
                    $tables = Table::where('table_status', 0)->where('site_id', $site_id)->with('Site')->get();
                } else {
                    $tables = Table::where('table_status', 0)->with('Site')->get();
                }
                break;
            case "2":
                if ($site_id !== null) {
                    $tables = Table::whereNotIn('table_status', [0, 1])->where('site_id', $site_id)->with('Site')->get();
                } else {
                    $tables = Table::whereNotIn('table_status', [0, 1])->with('Site')->get();
                }
                break;
            case "3":
                if ($site_id !== null) {
                    $tables = Table::where('table_status', 1)->where('site_id', $site_id)->with('Site')->get();
                } else {
                    $tables = Table::where('table_status', 1)->with('Site')->get();
                }

                break;
            default:
                if ($site_id !== null) {
                    $tables = Table::where('site_id', $site_id)->with('Site')->get();
                } else {
                    $tables = Table::with('Site')->get();
                }
                break;
        }

        $tableStats = $site_id === null ? array(
            'available' => Table::where('table_status', 0)->count(),
            'occupied' => Table::whereNotIn('table_status', [0, 1])->count(),
            'reserve' => Table::where('table_status', 1)->count(),
            'all' => Table::all()->count(),
        ) : array(
            'available' => Table::where('site_id', $site_id)->where('table_status', 0)->count(),
            'occupied' => Table::where('site_id', $site_id)->whereNotIn('table_status', [0, 1])->count(),
            'reserve' => Table::where('site_id', $site_id)->where('table_status', 1)->count(),
            'all' => Table::where('site_id', $site_id)->count(),
        );

        return response()->json(compact('tables', 'tableStats'), 200);
    }
}
