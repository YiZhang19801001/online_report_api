<?php
namespace App\Http\Controllers\Helpers;

use App\Docket;
use App\Shop;
use \Illuminate\Support\Facades\DB;

class DocketHelper
{
    public function __construct()
    {
        // prepare some constants
        $tz = new \DateTimeZone("Australia/Sydney");
        $this->today = new \DateTime("now", $tz);
        $this->tommorrow = new \DateTime("+1 day", $tz);

    }
    /**
     * function - helper function for api controllers generate array<Docket>
     *
     * @param Object $request
     * @return Array<Docket> $dockets
     */
    public function getDockets($request)
    {
        // ready inputs and sets default/initialize values
        $shop_id = $request->input("shop_id", 1);
        $startDate = $request->input("startDate", $this->today);
        $endDate = $request->input("endDate", $this->tommorrow);

        $inputs = compact('shop_id', 'startDate', 'endDate');

        return self::getTotalSales($inputs);

    }

    public function getTotalSales($inputs)
    {
        // find shop according to inputs shop_ip
        $shop = Shop::find($inputs['shop_id']);

        DB::purge();

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
        \Config::set('database.connections.sqlsrv.username', $shop->username);
        \Config::set('database.connections.sqlsrv.password', $shop->password);
        \Config::set('database.connections.sqlsrv.database', $shop->database_name);
        \Config::set('database.connections.sqlsrv.port', $shop->port);

        // read all dockets during the period
        return Docket::with("docketlines")->whereBetween('docket_date', [$inputs['startDate'], $inputs['endDate']])->whereIn('transaction', ["SA", "IV"])->sum('total_inc');

    }
}
