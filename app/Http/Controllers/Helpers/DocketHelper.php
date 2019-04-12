<?php
namespace App\Http\Controllers\Helpers;

use App\Docket;
use App\Shop;

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

        // find shop according to inputs shop_ip
        $shop = Shop::find($shop_id);

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $shop->database_ip);

        // read all dockets during the period
        return Docket::with("docketlines")->whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV")->sum('total_inc');
    }
}
