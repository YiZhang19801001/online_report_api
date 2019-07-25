<?php

namespace App\Http\Controllers;

use App\PosHeadShop;
use App\Shop;
use App\ShopToUser;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->user_type === 'CUSTOMER') {
            $shops = $user->shops()->get();
        } else if ($user->user_type === 'HEAD') {
            // find shop according to inputs shop_ip
            $shopId = $user->shops()->first()->shop_id;

            $shop = Shop::find($shopId);

            DB::purge();

            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
            \Config::set('database.connections.sqlsrv.username', $shop->username);
            \Config::set('database.connections.sqlsrv.password', $shop->password);
            \Config::set('database.connections.sqlsrv.database', $shop->database_name);
            \Config::set('database.connections.sqlsrv.port', $shop->port);

            $shops = PosHeadShop::where('shop_id', '>', 0)->get();
        } else {
            $shops = Shop::all();

        }
        return response()->json(compact("shops"));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->user_type === 'CUSTOMER') {
            return response()->json(['code' => 401, 'message' => 'you are not authorized to create a store']);
        }

        #read inputs

        $shop_name = $request->input('shop_name');
        $database_ip = $request->input('database_ip');
        $username = $request->input('username');
        $database_name = $request->input('database_name');
        $password = $request->input('password');
        $port = $request->input('port');

        $shop = Shop::create(compact('shop_name', 'database_ip', 'username', 'database_name', 'port', 'password'));
        $shopToUser = ShopToUser::create(['user_id' => $request->input('user_id'), 'shop_id' => $shop->shop_id]);

        return response()->json(['code' => 0, 'message' => 'success']);

    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if ($user->user_type === 'CUSTOMER') {
            return response()->json(['code' => 401, 'message' => 'you are not authorized to update a store']);
        }

        $shop = Shop::find($id);

        #read inputs

        $shop->shop_name = $request->input('shop_name');
        $shop->database_ip = $request->input('database_ip');
        $shop->username = $request->input('username');
        $shop->database_name = $request->input('database_name');
        $shop->password = $request->input('password');
        $shop->port = $request->input('port');

        #update shop
        $shop->save();

        return response()->json(['code' => 0, 'message' => 'success', 'shop' => $shop], 200);
    }

    public function delete(Request $request, $id)
    {
        $user = $request->user();
        if ($user->user_type === 'CUSTOMER') {
            return response()->json(['code' => 401, 'message' => 'you are not authorized to update a store']);
        }

        Shop::destroy($id);
        $shopToUsers = ShopToUser::where('shop_id', $id)->get();
        foreach ($shopToUsers as $shopToUser) {
            $shopToUser->delete();
        }

        return response()->json(['code' => 0, 'message' => 'success'], 200);

    }
}
