<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Helpers\ReportHelper;
use App\Shop;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->helper = new Reporthelper();
    }
    public function index(Request $request)
    {
        #read inputs

        $today = new \DateTime('now', new \DateTimeZone('Australia/Sydney'));

        $meta = $request->input('meta', 'dailySummary');

        $date = date('y-m-d H:i:s', strtotime($request->input('date', $today->format('YYYY-MM-DD'))));

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

        #call helper class to generate data
        // use switch to filter the meta in controller make codes more readable in helper class
        switch ($meta) {
            case 'dailySummary':
                $reports = $this->helper->getDailySummary($date);
                break;
            case 'weeklySummary':
                $reports = $this->helper->getWeeklySummary($date);
                break;
            case 'monthlySummary':
                break;
            case 'dataGroup':
                $reports = $this->helper->getDataGroup($date);
                break;
            default:
                # code...
                break;
        }

        $shops = $user->shops()->select('shop_name')->get();
        $path = 'summary';
        $reports['shops'] = $shops;
        return response()->json(compact('reports', 'path'), 200);

    }

    public function store(Request $request)
    {
        #read inputs

        $today = new \DateTime('now', new \DateTimeZone('Australia/Sydney'));

        $startDate = date('y-m-d H:i:s', strtotime($request->input('startDate', $today->format('YYYY-MM-DD'))));
        $endDate = date('y-m-d H:i:s', strtotime($request->input('endDate', $today->format('YYYY-MM-DD'))));

        $user = $request->user();

        // find shop according to inputs shop_ip
        $shops = $user->shops()->get();

        #call helper class to generate data
        $reports = $this->helper->getTotalSummary($shops, $startDate, $endDate);

        // use switch to filter the meta in controller make codes more readable in helper class

        $shops = $user->shops()->select('shop_name')->get();
        $path = 'totalSummary';
        $reports['shops'] = $shops;
        return response()->json(compact('reports', 'path'), 200);
    }
}
