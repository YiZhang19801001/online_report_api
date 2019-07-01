<?php

namespace App\Http\Controllers;

use App\ShopToUser;
use App\User;
use Illuminate\Http\Request;

class ShopToUserController extends Controller
{
    public function store(Request $request)
    {
        # read inputs
        $user_id = $request->input('user_id');
        $shop_id = $request->input('shop_id');

        # create inputs
        ShopToUser::create(compact('user_id', 'shop_id'));

        $user = User::find($user_id);
        $shops = $user->shops();

        return response()->json(['code' => 0, 'message' => 'success', 'shops' => $shops], 200);
    }

    public function delete(Request $request, $id)
    {
        #read inputs
        $user_id = $request->input('user_id');

        $shopToUser = ShopToUser::find($id);

        $shopToUser->delete();

        $user = User::find($user_id);

        $shops = $user->shops();

        return response()->json(['code' => 0, 'message' => 'success', 'shops' => $shops], 200);
    }
}
