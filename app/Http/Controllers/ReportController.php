<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Helpers\ReportHelper;
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

        $date = date('y-m-d H:i:s', strtotime($request->input('date', $today)));

        $user = $request->user();

        // find shop according to inputs shop_ip
        $shop = $user->shops()->first();

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
                break;
            case 'monthlySummary':
                break;
            case 'dataGroup':
                $reports = $this->helper->getDataGroup($date);
            default:
                # code...
                break;
        }

        return response()->json(compact('reports'), 200);

    }
}
