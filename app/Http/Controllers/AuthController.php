<?php
namespace App\Http\Controllers;

use App\PosHeadShop;
use App\Shop;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            // 'email' => 'required|string|email|unique:users',
            'password' => 'required|string',
        ]);
        $user = new User([
            'name' => $request->name,
            // 'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->user_type = $request->input('user_type', 'CUSTOMER');
        $user->use_history = $request->input('use_history', 1);

        $user->save();

        return response()->json([
            'code' => '0',
            'message' => 'Successfully created user!',
        ], 201);
    }

    /**
     * Login user and create token
     *
     * @param  [string] name
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);
        $credentials = request(['name', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');

        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }

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
        } else if ($user->user_type === 'GIFTSHOPHEAD') {
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

            $shops = PosHeadShop::where('shop_id', '>', 0)->where('inactive', 0)->get();
        } else {
            $shops = Shop::all();

        }

        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user_type' => $user->user_type,
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'shops' => $shops,
            'cups_report' => $user->cups_report,
            'tables_report' => $user->tables_report,
            'customer_report' => $user->customer_report,
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
