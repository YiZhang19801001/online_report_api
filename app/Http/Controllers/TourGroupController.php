<?php

namespace App\Http\Controllers;

use App\Shop;
use App\TourGroup;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class TourGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        # read input
        $user = $request->user();
        $startDate = new \DateTime($request->startDate, new \DateTimeZone('Australia/Sydney'));
        $endDate = new \DateTime($request->endDate, new \DateTimeZone('Australia/Sydney'));

        if ($user->user_type === "GIFTSHOPHEAD" && $user->name === 'giftshop') {
            $shopId = $user->shops()->first()->shop_id;

            $shop = Shop::find($shopId);

            try {
                DB::purge();

                // set connection database ip in run time
                \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
                \Config::set('database.connections.sqlsrv.username', $shop->username);
                \Config::set('database.connections.sqlsrv.password', $shop->password);
                \Config::set('database.connections.sqlsrv.database', $shop->database_name);
                \Config::set('database.connections.sqlsrv.port', $shop->port);

                $groupIds = TourGroup::
                    where('date_start', '>', $startDate)
                    ->where('date_end', '<', $endDate)
                    ->select('group_id', 'group_name')
                    ->get();
                $code = "0";
                $message = "success";
            } catch (\Throwable $th) {
                $code = "1";
                $groupIds = [];
                $message = "can not connect to database";
            }

        } else if ($user->user_type === "GIFTSHOPHEAD" && $user->name === 'lisa') {
            $shopId = $user->shops()->first()->shop_id;

            $shop = Shop::find($shopId);

            try {
                DB::purge();

                // set connection database ip in run time
                \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
                \Config::set('database.connections.sqlsrv.username', $shop->username);
                \Config::set('database.connections.sqlsrv.password', $shop->password);
                \Config::set('database.connections.sqlsrv.database', $shop->database_name);
                \Config::set('database.connections.sqlsrv.port', $shop->port);

                $groupIds = TourGroup::
                    where('start_date', '>', $startDate)
                    ->where('end_date', '<', $endDate)
                    ->select('group_id', 'group_code as group_name')
                    ->get();
                $code = "0";
                $message = "success";
            } catch (\Throwable $th) {
                $code = "1";
                $groupIds = [];
                $message = "can not connect to database";
            }
        }

        return response()->json(compact("code", "groupIds", "message"));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
