<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
        $user = User::find($id);

        $user->reset_password = 0;
        $user->password = bcrypt('abc123');

        $user->save();

        return response()->json(['code' => '0', 'message' => 'password has been reset for customer: ' . $user->name], 200);

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
        $password = bcrypt($request->password);

        if ($user->reset_password == 0) {
            $user->password = $password;
            $user->reset_password = 1;
            $user->save();

            return response()->json(['code' => '0', 'message' => 'your password success updated'], 200);

        } else {
            return response()->json(['code' => '1', 'message' => 'fail to update your password, because your password has been changed before, please contact our staff to reset your password first, then try to update your password again.'], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // $user = User::find($id);
        // $password = bcrypt('abc123');

        // return response()->json(['code' => '0', 'message' => 'your password success updated'], 200);

    }
}
