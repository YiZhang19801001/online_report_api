<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Helpers\DocketHelper;
use Illuminate\Http\Request;

class DocketController extends Controller
{
    public function __construct()
    {
        $this->docketHelper = new DocketHelper();
    }

    /**
     * function - return all dockets in selected time period [ today as default]
     *
     * @param Request $request, could contain ['shop_id','startDate','endDate']
     * @return Response json object $dockets
     */
    public function index(Request $request)
    {
        $dockets = $this->docketHelper->getDockets($request);

        return response()->json(compact("dockets"), 200);
    }
}
