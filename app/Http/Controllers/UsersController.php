<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::whereIn('user_type', ['CUSTOMER', 'HEAD'])->with('shops')->get();
        $code = "0";
        return response()->json(compact('code', 'users'), 200);
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        $user->cups_report = $request->input('cups_report', $user->cups_report);
        $user->customer_report = $request->input('customer_report', $user->customer_report);
        $user->tables_report = $request->input('tables_report', $user->tables_report);
        $user->export_report = $request->input('export_report', $user->export_report);

        if (isset($request->use_history)) {
            $user->use_history = $request->use_history;
        }

        $user->save();

        return response()->json(['code' => '0', 'message' => 'success'], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $shops = $user->shops()->get();
        if (count($shops) == 0) {
            $user->delete();
            return response()->json(['code' => '0', 'message' => 'success delete'], 200);
        } else {
            return response()->json(['code' => '1', 'message' => 'you can remove this user, because there are shops linked with him.'], 200);
        }

    }
}
