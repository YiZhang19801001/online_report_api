<?php

namespace App\Http\Controllers;

use App\PosHeadShop;
use App\User;
use Illuminate\Http\Request;
use PDF;
use \Illuminate\Support\Facades\DB;

class PDFController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $headOffice = $user->shops()->first();

        # connect to head office db
        DB::purge();

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $headOffice->database_ip);
        \Config::set('database.connections.sqlsrv.username', $headOffice->username);
        \Config::set('database.connections.sqlsrv.password', $headOffice->password);
        \Config::set('database.connections.sqlsrv.database', $headOffice->database_name);
        \Config::set('database.connections.sqlsrv.port', $headOffice->port);

        # found shop
        $shop = PosHeadShop::where('shop_name', $request->shop_name)->first();

        # connect to shop db
        # found database connect credentials
        $db_path_array = explode(';', $shop->db_path);
        $db_password = explode(';', $shop->db_password);

        $database_ip = explode('=', $db_path_array[0])[1];
        $database_name = explode('=', $db_path_array[1])[1];
        $username = explode('=', $db_password[0])[1];
        $password = explode('=', $db_password[1])[1];

        # connect to DB
        DB::purge('sqlsrv');

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $database_ip);
        \Config::set('database.connections.sqlsrv.username', $username);
        \Config::set('database.connections.sqlsrv.password', $password);
        \Config::set('database.connections.sqlsrv.database', $database_name);
        \Config::set('database.connections.sqlsrv.port', 1433);

        $sqlResult = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Customer', 'Docket.customer_id', '=', 'Customer.customer_id')
            ->join('Stock', 'DocketLine.stock_id', '=', 'Stock.stock_id')
        // ->where('Docket.customer_id',$customer_id)
            ->where('Customer.given_names', $request->group_code)
            ->selectRaw('Stock.Barcode, Customer.customer_id, Stock.[description] as [description],DocketLine.[cost_inc],DocketLine.sell_inc,DocketLine.[quantity] AS [qty], DocketLine.gp AS [line_gp], DocketLine.sell_inc * DocketLine.quantity as [extension]')
            ->orderBy('Customer.customer_id', 'Stock.Barcode')
            ->get();

        $totalQty = 0;
        $totalSale = 0;

        foreach ($sqlResult as $value) {
            # code...DocketLine.gp/DocketLine.sell_inc as [gp]
            $value->gp = $value->line_gp / ($value->sell_inc != 0 ? $value->sell_inc : 1);
            $totalQty += $value->qty;
            $totalSale += $value->extension;
        }

        $data['title'] = 'Profit Report (By Group)';
        $data['shopName'] = $request->input('shop_name', " ");
        $data['groupCode'] = $request->input('group_code', " ");

        $data['reports'] = $sqlResult;

        $fileName = $request->shop_name . "-" . $request->group_code . ".pdf";

        $pdf = PDF::loadView('profit_report', $data);
        $pdf->save($fileName);

        $fileUrl = "http://101.187.98.39:8181" . "/online_report_api/public/" . $fileName;
        $code = "0";
        return response()->json(compact('code', 'fileUrl'));
    }
}
