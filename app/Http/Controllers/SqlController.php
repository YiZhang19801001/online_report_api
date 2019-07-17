<?php

namespace App\Http\Controllers;

use App\Shop;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class SqlController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $shopId = $user->shops()->first()->shop_id;

        $shop = Shop::find($shopId);

        DB::purge();

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
        \Config::set('database.connections.sqlsrv.username', $shop->username);
        \Config::set('database.connections.sqlsrv.password', $shop->password);
        \Config::set('database.connections.sqlsrv.database', $shop->database_name);
        \Config::set('database.connections.sqlsrv.port', $shop->port);

        $startDate = '2019-07-17';
        $endDate = '2019-07-18';
        // $Dockets = Docket::whereBetween('docket_date', [$startDate, $endDate])->take(20)->get();

        // $result = array();
        // foreach ($Dockets as $dkt) {
        //     $dlsSum = DocketLine::where('docket_id', $dkt->docket_id)->sum('sell_inc');

        //     array_push($result, ['total' => $dkt->total_inc, 'sum' => $dlsSum]);

        // }

        $result = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
        // ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
        // ->where('Stock.stock_id', '>', 0)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->whereIn('Docket.transaction', ["SA", "IV"])
            ->selectRaw('Docket.shop_id, sum((DocketLine.sell_ex - DocketLine.cost_ex) * DocketLine.quantity) as gp ,sum(DocketLine.RRP - DocketLine.sell_inc) as discount,count(DISTINCT Docket.Docket_id) as totalTx,sum(DocketLine.sell_inc* DocketLine.quantity) as totalSales,sum(abs(DocketLine.sell_inc)) as absTotal')
            ->groupBy('Docket.shop_id')->get();

        foreach ($result as $item) {
            # calculate totalRefund
            $item->totalRefund = $item->totalSales - $item->absTotal;
            $item->gp_percentage = $item->totalSales != 0 ? $item->gp / $item->totalSales : 0;
            // foreach ($shops as $shop) {
            //     if ($shop->shop_id === $item->shop_id) {
            //         $item->shop = ['shop_id' => $shop->shop_id, 'shop_name' => $shop->shop_name];
            //     }
            // }

        }

        return response()->json(compact('result'), 200);
    }
}
