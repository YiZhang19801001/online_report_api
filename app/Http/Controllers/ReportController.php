<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Helpers\HeadReportHelper;
use App\Http\Controllers\Helpers\PosReportHelper;
use App\PosHeadShop;
use App\Shop;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->helper = new PosReporthelper();
        $this->headHelper = new HeadReportHelper();
    }
    public function index(Request $request)
    {
        #read inputs

        $today = new \DateTime('now', new \DateTimeZone('Australia/Sydney'));

        $meta = $request->input('meta', 'dailySummary');

        $date = date('y-m-d H:i:s', strtotime($request->input('date', $today->format('YYYY-MM-DD'))));

        $user = $request->user();

        if ($user->user_type === 'HEAD') {
            $shopId = $user->shops()->first()->shop_id;
            $shop = Shop::find($shopId);
            DB::purge();

            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
            \Config::set('database.connections.sqlsrv.username', $shop->username);
            \Config::set('database.connections.sqlsrv.password', $shop->password);
            \Config::set('database.connections.sqlsrv.database', $shop->database_name);
            \Config::set('database.connections.sqlsrv.port', $shop->port);

            #call helper class to generate data
            // use switch to filter the meta in controller make codes more readable in helper class
            switch ($meta) {
                case 'dailySummary':
                    $reports = $this->headHelper->getDailySummary($date, $request->shopId, $user);
                    break;
                case 'weeklySummary':
                    $reports = $this->headHelper->getWeeklySummary($date, $request->shopId, $user);
                    break;

                case 'dataGroup':
                    $reports = $this->headHelper->getDataGroup($date, $request->shopId, $user);
                    break;
                default:
                    $reports = array();
                    break;
            }

            // this variable is not used currently
            $path = 'summary';

            $reports['shops'] = PosHeadShop::where('shop_id', '>', 0)->get();

        } else if ($user->user_type === 'CUSTOMER') {
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

            #call helper class to generate data
            // use switch to filter the meta in controller make codes more readable in helper class
            switch ($meta) {
                case 'dailySummary':
                    $reports = $this->helper->getDailySummary($date, $user);
                    break;
                case 'weeklySummary':
                    $reports = $this->helper->getWeeklySummary($date, $user);
                    break;

                case 'dataGroup':
                    $reports = $this->helper->getDataGroup($date, $user);
                    break;
                default:
                    # code...
                    break;
            }

            $shops = $user->shops()->get();
            $path = 'summary';
            $reports['shops'] = $shops;
        }

        return response()->json(compact('reports', 'path'), 200);

    }

    public function store(Request $request)
    {
        #read inputs
        $today = new \DateTime('now', new \DateTimeZone('Australia/Sydney'));

        $startDate = new \DateTime($request->startDate, new \DateTimeZone('Australia/Sydney'));
        $endDate = new \DateTime($request->endDate, new \DateTimeZone('Australia/Sydney'));

        $user = $request->user();

        if ($user->user_type === 'CUSTOMER') {
            // find shop according to inputs shop_ip
            $shops = $user->shops()->get();

            #call helper class to generate data
            $reports = $this->helper->getTotalSummary($shops, $startDate, $endDate, $user);
            // use switch to filter the meta in controller make codes more readable in helper class

            $shops = $user->shops()->select('shop_name')->get();
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
            #call helper class to generate data
            $reports = $this->headHelper->getTotalSummary($shops, $startDate, $endDate, $user);

        }
        $path = 'totalSummary';
        // $reports['shops'] = $shops;
        return response()->json(compact('reports', 'path'), 200);
    }

    public function update(Request $request, $id)
    {
        # read input
        $startDate = new \DateTime($request->startDate, new \DateTimeZone('Australia/Sydney'));
        $endDate = new \DateTime($request->endDate, new \DateTimeZone('Australia/Sydney'));
        $reportType = isset($request->reportType) ? $request->reportType : 'product';
        $user = $request->user();

        if ($user->user_type === 'CUSTOMER') {
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

            #call helper class to generate data
            // use switch to filter the meta in controller make codes more readable in helper class
            switch ($reportType) {
                case 'product':
                    $reports = $this->helper->getReportByProduct($startDate, $endDate, $user);
                    break;
                case 'category':
                    $reports = $this->helper->getReportByCategory($startDate, $endDate, $user);
                    break;
                case 'day':
                    $reports = $this->helper->getReportByDay($startDate, $endDate, $user);
                    break;
                case 'hour':
                    $reports = $this->helper->getReportByHour($startDate, $endDate, $user);
                    break;
                case 'customer':
                    $reports = $this->helper->getReportByCustomer($startDate, $endDate, $user);
                    break;
                default:
                    $reports = $this->helper->getReportByCategory($startDate, $endDate, $user);
                    break;
            }
        } else if ($user->user_type === 'HEAD') {
            $shop = $user->shops()->first();

            DB::purge();

            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
            \Config::set('database.connections.sqlsrv.username', $shop->username);
            \Config::set('database.connections.sqlsrv.password', $shop->password);
            \Config::set('database.connections.sqlsrv.database', $shop->database_name);
            \Config::set('database.connections.sqlsrv.port', $shop->port);

            #call helper class to generate data
            // use switch to filter the meta in controller make codes more readable in helper class
            switch ($reportType) {
                case 'product':
                    $reports = $this->headHelper->getReportByProduct($startDate, $endDate, $request->shopId, $user);
                    break;
                case 'category':
                    $reports = $this->headHelper->getReportByCategory($startDate, $endDate, $request->shopId, $user);
                    break;
                case 'day':
                    $reports = $this->headHelper->getReportByDay($startDate, $endDate, $request->shopId, $user);
                    break;
                case 'hour':
                    $reports = $this->headHelper->getReportByHour($startDate, $endDate, $request->shopId, $user);
                    break;
                case 'customer':
                    $reports = $this->headHelper->getReportByCustomer($startDate, $endDate, $request->shopId, $user);
                    break;
                default:
                    $reports = $this->headHelper->getReportByCategory($startDate, $endDate, $request->shopId, $user);
                    break;
            }
        }

        return response()->json(compact('reports'), 200);

    }
}
