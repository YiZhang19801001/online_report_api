<?php

namespace App\Http\Controllers;

use App\TourAgent;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->user_type === "GIFTSHOPHEAD") {
            $shop = $user->shops()->first();

            try {
                DB::purge();

                // set connection database ip in run time
                \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
                \Config::set('database.connections.sqlsrv.username', $shop->username);
                \Config::set('database.connections.sqlsrv.password', $shop->password);
                \Config::set('database.connections.sqlsrv.database', $shop->database_name);
                \Config::set('database.connections.sqlsrv.port', $shop->port);

                $tourAgentNames = TourAgent::
                    select('agent_name')
                    ->get();
                $code = "0";
                $message = "success";
            } catch (\Throwable $th) {
                $code = "1";
                $groupIds = [];
                $message = "can not connect to database";
            }

        }

        return response()->json(compact("code", "tourAgentNames", "message"));
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
