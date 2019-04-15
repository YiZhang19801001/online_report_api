<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Helpers\ReportHelper;
use Illuminate\Http\Request;

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
        $date = $request->input('date', $today);

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

            default:
                # code...
                break;
        }

        return response()->json(compact('reports'), 200);

    }
}
