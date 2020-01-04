<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Helpers\GiftShopHeadHelper;
use App\Http\Controllers\Helpers\HeadReportHelper;
use App\Http\Controllers\Helpers\PosReportHelper;
use App\PosHeadShop;
use App\Shop;
use App\TourGroup;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->helper = new PosReporthelper();
        $this->headHelper = new HeadReportHelper();
        $this->giftShopHeadHelper = new GiftShopHeadHelper();
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
        } else if ($user->user_type === 'GIFTSHOPHEAD') {
            $shopId = $user->shops()->first()->shop_id;
            $shop = Shop::find($shopId);

            DB::purge();

            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
            \Config::set('database.connections.sqlsrv.username', $shop->username);
            \Config::set('database.connections.sqlsrv.password', $shop->password);
            \Config::set('database.connections.sqlsrv.database', $shop->database_name);
            \Config::set('database.connections.sqlsrv.port', $shop->port);
            $reports['shops'] = PosHeadShop::where('shop_id', '>', 0)->get();

            $posHeadShop = PosHeadShop::find($request->shopId);
            #call helper class to generate data
            // use switch to filter the meta in controller make codes more readable in helper class
            switch ($meta) {
                case 'dailySummary':

                    $reports = $this->giftShopHeadHelper->getDailySummary($date, $posHeadShop, $user);
                    break;
                case 'weeklySummary':
                    $reports = $this->giftShopHeadHelper->getWeeklySummary($date, $posHeadShop, $user);
                    break;

                case 'dataGroup':
                    $reports = $this->giftShopHeadHelper->getDataGroup($date, $posHeadShop, $user);
                    break;
                default:
                    $reports = array();
                    break;
            }

            // this variable is not used currently
            $path = 'summary';

        }

        return response()->json(compact('reports', 'path'), 200);

    }

    public function store(Request $request)
    {
        try {
            #read inputs
            $today = new \DateTime('now', new \DateTimeZone('Australia/Sydney'));

            $startDate = new \DateTime($request->startDate, new \DateTimeZone('Australia/Sydney'));
            $endDate = new \DateTime($request->endDate, new \DateTimeZone('Australia/Sydney'));

            $user = $request->user();

            if ($user->user_type === 'CUSTOMER') {
                // find shop according to inputs shop_ip
                $shops = $user->shops()->get();

                #call helper class to generate data
                $result = $this->helper->getTotalSummary($shops, $startDate, $endDate, $user);
                // use switch to filter the meta in controller make codes more readable in helper class
                $reports = $result['reports'];
                $shops = $result['shops'];
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

                $shops = PosHeadShop::where('shop_id', '>', 0)->where('inactive', '!=', 1)->get();
                #call helper class to generate data
                $reports = $this->headHelper->getTotalSummary($shops, $startDate, $endDate, $user);

            } else if ($user->user_type === "GIFTSHOPHEAD") {
                // find shop according to inputs shop_ip
                $shop = $user->shops()->first();

                DB::purge();

                // set connection database ip in run time
                \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
                \Config::set('database.connections.sqlsrv.username', $shop->username);
                \Config::set('database.connections.sqlsrv.password', $shop->password);
                \Config::set('database.connections.sqlsrv.database', $shop->database_name);
                \Config::set('database.connections.sqlsrv.port', $shop->port);

                $shops = PosHeadShop::where('shop_id', '>', 0)->where('inactive', 0)->get();
                #call helper class to generate data
                switch ($request->reportType) {
                    case 'shop':
                        $reports = $this->giftShopHeadHelper->getShopTotalSummary($shops, $startDate, $endDate, $user);
                        break;
                    case 'group':
                        $groupId = $request->groupId;
                        $reports = $this->giftShopHeadHelper->getGroupSalesSummary($shops, $startDate, $endDate, $groupId, $user);
                        break;
                    case 'agent':
                        if ($user->name === 'lisa') {
                            $groupNames = TourGroup::
                                whereBetween('start_date', [$startDate, $endDate])
                                ->select('group_code')->get();
                            $groupNameToPax = TourGroup::
                                whereBetween('start_date', [$startDate, $endDate])
                                ->select('group_code as group_name', 'pax')->get();
                        } else {
                            $groupNames = TourGroup::
                                whereBetween('date_start', [$startDate, $endDate])
                                ->select('group_name')->get();

                            $groupNameToPax = TourGroup::
                                whereBetween('date_start', [$startDate, $endDate])
                                ->select('group_name', 'pax')->get();

                        }
                        // var_dump($groupNameToPax);
                        // return response()->json(compact('groupNameToPax'), 200);
                        $agentName = $request->input("agentName", "");
                        $reports = $this->giftShopHeadHelper->getAgentSalesSummary($shops, $startDate, $endDate, $user, $agentName, $groupNames, $groupNameToPax);

                        break;
                    default:
                        $reports = $this->giftShopHeadHelper->getShopTotalSummary($shops, $startDate, $endDate, $user);
                        break;
                }
            }
            $path = 'totalSummary';
            return response()->json(compact('reports', 'path', 'shops'), 200);
        } catch (\Throwable $th) {
            return response()->json(['errors' => $th->getMessage()], 400);
        }
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
        } else if ($user->user_type === 'GIFTSHOPHEAD') {
            $shop = $user->shops()->first();

            DB::purge();

            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
            \Config::set('database.connections.sqlsrv.username', $shop->username);
            \Config::set('database.connections.sqlsrv.password', $shop->password);
            \Config::set('database.connections.sqlsrv.database', $shop->database_name);
            \Config::set('database.connections.sqlsrv.port', $shop->port);
            $reports['shops'] = PosHeadShop::where('shop_id', '>', 0)->get();
            $posHeadShop = PosHeadShop::find($request->shopId);

            #call helper class to generate data
            // use switch to filter the meta in controller make codes more readable in helper class
            switch ($reportType) {
                case 'product':
                    $reports = $this->giftShopHeadHelper->getReportByProduct($startDate, $endDate, $posHeadShop, $user);
                    break;
                case 'category':
                    $reports = $this->giftShopHeadHelper->getReportByCategory($startDate, $endDate, $posHeadShop, $user);
                    break;
                case 'day':
                    $reports = $this->giftShopHeadHelper->getReportByDay($startDate, $endDate, $posHeadShop, $user);
                    break;
                case 'hour':
                    $reports = $this->giftShopHeadHelper->getReportByHour($startDate, $endDate, $posHeadShop, $user);
                    break;

                case 'staff':
                    $reports = $this->giftShopHeadHelper->getReportByStaff($startDate, $endDate, $posHeadShop, $user);
                    break;
                case 'customer':
                    $reports = $this->giftShopHeadHelper->getReportByCustomer($startDate, $endDate, $posHeadShop, $user);
                    break;
                default:
                    $reports = $this->giftShopHeadHelper->getReportByCategory($startDate, $endDate, $posHeadShop, $user);
                    break;
            }
        }

        return response()->json(compact('reports'), 200);

    }
}
