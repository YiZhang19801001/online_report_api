<?php
namespace App\Http\Controllers\Helpers;

use App\Docket;
use App\HistDocket;
use App\HistPayments;
use App\Stock;
use \Illuminate\Support\Facades\DB;
use App\TourGroup;
use App\Shop;
use App\Customer;
use App\PosHeadShop;

class GiftShopHeadHelper
{
    public function __construct()
    {
        // prepare some constants
        $tz = new \DateTimeZone("Australia/Sydney");
        $this->today = new \DateTime("now", $tz);
        $this->tommorrow = new \DateTime("+1 day", $tz);
        $this->yesterday = new \DateTime("-1 day", $tz);
    }
    /**
     * function - generate daily summay
     *
     * @param [DateTime] $date
     * @return Object ['date','sales','numberOfTransactions','paymentMethod']
     */
    public function getDailySummary($dateTime, $shop, $user)
    {
        $dt = self::makeDateTime($dateTime);
        $date = $dt->format('Y-m-d');
        $stopDate = date('Y-m-d H:i:s', strtotime($date . '+1 day'));
        $yesterday = date('Y-m-d H:i:s', strtotime($date . '-1 day'));

        // $shop = PosHeadShop::find($shopId);

        # found database connect credentials
        $db_path_array = explode(';', $shop->db_path);
        $db_password = explode(';', $shop->db_password);

        $database_ip = explode('=', $db_path_array[0])[1];
        $database_name = explode('=', $db_path_array[1])[1];
        $username = explode('=', $db_password[0])[1];
        $password = explode('=', $db_password[1])[1];

        # generate group report for this shop
        // try {
        # connect to DB
        DB::purge('sqlsrv');

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $database_ip);
        \Config::set('database.connections.sqlsrv.username', $username);
        \Config::set('database.connections.sqlsrv.password', $password);
        \Config::set('database.connections.sqlsrv.database', $database_name);
        \Config::set('database.connections.sqlsrv.port', 1433);    
        
        $sql = Docket::whereBetween('docket_date', [$date, $stopDate]);
        $sales = $sql->sum('total_inc');
        $numberOfTransactions = $sql->count();
        
        $compareSql = Docket::whereBetween('docket_date', [$yesterday, $date]);
        $compareSales = $compareSql->sum('total_inc');
        $compareNumberOfTransactions = $compareSql->count();

        $reportsForPaymentMethod = self::reportsForPaymentMethod($date, $stopDate, $user);
        
        return compact('date', 'stopDate', 'sales', 'compareSales', 'compareNumberOfTransactions', 'numberOfTransactions', 'reportsForPaymentMethod', 'resource');

    }

    public function getWeeklySummary($dateTime, $shop, $user)
    {
        # read inputs preparing data.
        $dtString = date('Y-m-d', strtotime($dateTime . '-1 month'));
        $dt = self::makeDateTime($dtString);
        $month = $dt->format('m');
        $year = $dt->format('Y');
        $startDate = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 01, $year));
        $endDate = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $dt->format('t'), $year));
        $weeks = self::makeWeeks($dateTime);
        $weeklyReports = array();

        # found database connect credentials
        $db_path_array = explode(';', $shop->db_path);
        $db_password = explode(';', $shop->db_password);

        $database_ip = explode('=', $db_path_array[0])[1];
        $database_name = explode('=', $db_path_array[1])[1];
        $username = explode('=', $db_password[0])[1];
        $password = explode('=', $db_password[1])[1];

        # generate group report for this shop
        // try {
        # connect to DB
        DB::purge('sqlsrv');

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $database_ip);
        \Config::set('database.connections.sqlsrv.username', $username);
        \Config::set('database.connections.sqlsrv.password', $password);
        \Config::set('database.connections.sqlsrv.database', $database_name);
        \Config::set('database.connections.sqlsrv.port', 1433);

        $sales = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->sum('total_inc');
        $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->count();
        $comparison = ['date' => $startDate, 'sales' => $sales, 'tx' => $tx];
        foreach ($weeks as $week) {
            $report = self::getWeeklyReport($week, $user->use_history);
            $report['from'] = $week['from'];
            $report['to'] = $week['to'];
            $report['paymentMethodReports'] = self::reportsForPaymentMethod($week['from'], $week['to'], $user);
            array_push($weeklyReports, $report);
        }
        

        return compact('weeklyReports', 'weeks', 'comparison', 'startDate', 'endDate');
    }

    public function reportsForPaymentMethod($start, $end, $user)
    {
        $groups = DB::connection('sqlsrv')->table('Payments')
            ->join('Docket', 'Payments.docket_id', '=', 'Docket.docket_id')
            ->whereBetween('Docket.docket_date', [$start, $end])
            ->whereIn('Docket.transaction', ["SA", "IV"])
            ->selectRaw('Payments.paymenttype,sum(Payments.amount) as total')
            ->groupBy('Payments.paymenttype')
            ->get();
   

        return $groups;
    }

    public function getDataGroup($dateTime, $shopId)
    {

        $dt = new \DateTime($dateTime, new \DateTimeZone('Australia/Sydney'));
        $startDate = $dt->format('Y-m-d');
        $endDate = date('Y-m-d H:i:s', strtotime($startDate . '+1 day'));

        $dataGroup = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Stock.stock_id', '>', 0)
            ->where('Docket.shop_id', $shopId)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->whereIn('Docket.transaction', ["SA", "IV"])
            ->where('Stock.cat1', '!=', 'TASTE')
            ->where('Stock.cat1', '!=', 'EXTRA')
            ->where('Stock.cat1', '!=', null)
            ->selectRaw('DocketLine.size_level,sum(DocketLine.quantity) as quantity')
            ->groupBy('DocketLine.size_level')
            ->get();

        $dataGroupDetails = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Docket.shop_id', $shopId)
            ->where('Stock.stock_id', '>', 0)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->whereIn('Docket.transaction', ["SA", "IV"])
            ->where('Stock.cat1', '!=', 'TASTE')
            ->where('Stock.cat1', '!=', 'EXTRA')
            ->where('Stock.cat1', '!=', null)
            ->get();

        foreach ($dataGroup as $item) {
            switch ($item->size_level) {
                case 0:
                    $item->size = 'others services';
                    break;
                case 1:
                    $item->size = Stock::where('custom1', '!=', "")->where('custom1', '!=', null)->where('cat1', '!=', null)->first()->custom1;
                    break;
                case 2:
                    $item->size = Stock::where('custom2', '!=', "")->where('custom2', '!=', null)->where('cat1', '!=', null)->first()->custom2;
                    break;
                default:
                    $item->size = 'unknown size';
                    break;
            }
        }

        return compact('dataGroup', 'dataGroupDetails');
    }

    public function getShopTotalSummary($shops, $startDate, $endDate, $user)
    {
        $reports = [];

        // should change the connection for each db than calculate summary for each shop
        foreach ($shops as $shop) {
            $shopReport = self::getShopSummary($startDate, $endDate, $shop);
            array_push($reports, $shopReport);
        }

        return $reports;
    }

    public function getGroupSalesSummary($shops,$startDate,$endDate,$groupId,$user)
    {

        # connect to pos head database;
        $shopId = $user->shops()->first()->shop_id;

        $shop = Shop::find($shopId);

        DB::purge();

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
        \Config::set('database.connections.sqlsrv.username', $shop->username);
        \Config::set('database.connections.sqlsrv.password', $shop->password);
        \Config::set('database.connections.sqlsrv.database', $shop->database_name);
        \Config::set('database.connections.sqlsrv.port', $shop->port);

        $group = TourGroup::find($groupId);
        
        $group_name = $group->group_name;
        
        $totalSales = 0;

        $reports = [];

        // should change the connection for each db than calculate summary for each shop
        foreach ($shops as $shop) {
            $shopReport = self::getGroupReports($startDate, $endDate, $shop,$groupId,$group_name);
            if($shopReport!=null){
                $customer = Customer::find($shopReport->customer_id);
                $totalSales += $shopReport->totalSales;
                array_push($reports, array(
                    "sale"=>$shopReport->totalSales,
                    "shopName"=>$shop->shop_name,
                    "date"=>$customer->date_modified,
                    "guide"=>$customer->addr3,
                    "avg"=>$shopReport->totalSales/($group->pax ===0 ?1:$group->pax),
                    // todo:: finish special logic
                    "specialSale"=>[],
                    "comments"=>$customer->notes,
                ));
            }else{
                array_push($reports, array(
                    "sale"=>0,
                    "shopName"=>$shop->shop_name,
                    "date"=>"",
                    "guide"=>"",
                    "avg"=>0,
                    // todo:: finish special logic
                    "specialSale"=>[],
                    "comments"=>"",
                ));
            }
        }

        $group->totalSales = $totalSales;
        $group->avg = $totalSales/($group->pax ===0 ?1:$group->pax);

        return ['groupSummary'=>$group,'reports'=>$reports];
    }


    public function getAgentSalesSummary($shops, $startDate, $endDate, $user,$agentName,$groupNames)
    {
        $reports = [];
        $initShopTotal = [];
        foreach ($shops as  $eleShop) {
            $initShopTotal[$eleShop->barcode] = 0;
        }
        # selected reports group by tour group name and tour agent
        // should change the connection for each db than calculate summary for each shop
        foreach ($shops as $shop) {
            $agentReport = self::getAgentReport($startDate, $endDate, $shop,$agentName,$groupNames,$user);
            if($agentReport!=null){
                $agentReport = json_decode(json_encode($agentReport));
                foreach ($agentReport->reports as  $agentReportItem) {
                    if(!array_key_exists($agentReportItem->agentName,$reports)){
                        # create new agent to reports                       
                        $reports[$agentReportItem->agentName] = ["summary"=>["pax"=>0,"shopTotal"=>$initShopTotal,"subTotal"=>0,"perhead"=>0],"reports"=>[]];
                        
                        $reports[$agentReportItem->agentName]['reports'][$agentReportItem->groupName] = ["pax"=>$agentReportItem->pax,"kb"=>$agentReportItem->kb==1?"HF":"%","shopReports"=>$initShopTotal];

                        # mapping values
                        $sale = $agentReportItem->totalSales ? $agentReportItem->totalSales : 0;
                        $reports[$agentReportItem->agentName]["summary"]["pax"] += $agentReportItem->pax;
                        $reports[$agentReportItem->agentName]['reports'][$agentReportItem->groupName]["shopReports"][$agentReport->shopName] = $sale + 0;
                        $reports[$agentReportItem->agentName]['summary']['subTotal'] = $sale + 0;
                    }   
                    else{
                        # add new reports to existing agent
                        $sale = $agentReportItem->totalSales ? $agentReportItem->totalSales : 0;
                        $reports[$agentReportItem->agentName]['summary']['subTotal'] += $sale;
                        
                        if(array_key_exists($agentReportItem->groupName,$reports[$agentReportItem->agentName]['reports'])) {
                
                            # mapping values
                            $reports[$agentReportItem->agentName]['reports'][$agentReportItem->groupName]["shopReports"][$agentReport->shopName] = $sale + 0;
  
                        }else{
                            # create new group/shop report to agent
                            $reports[$agentReportItem->agentName]['reports'][$agentReportItem->groupName] = ["pax"=>$agentReportItem->pax,"kb"=>$agentReportItem->kb==1?"HF":"%","shopReports"=>$initShopTotal];
                            # mapping values
                            $reports[$agentReportItem->agentName]["summary"]["pax"] += $agentReportItem->pax;                            
                            $reports[$agentReportItem->agentName]['reports'][$agentReportItem->groupName]["shopReports"][$agentReport->shopName] = $sale + 0;
                        }
                    }
                    if(array_key_exists($agentReport->shopName,$reports[$agentReportItem->agentName]['summary']['shopTotal'])){
                                
                        $reports[$agentReportItem->agentName]['summary']['shopTotal'][$agentReport->shopName] += $sale;
                    }else{
                        $reports[$agentReportItem->agentName]['summary']['shopTotal'][$agentReport->shopName] = $sale + 0;
                    }                 
                }
            }
            // array_push($reports,$agentReport);
        }

        foreach ($reports as $key=>$ele) {
            $reports[$key]['summary']['perhead'] = $ele['summary']['subTotal'] / ($ele['summary']['pax']==0?1:$ele['summary']['pax']);
        }

        return $reports;

    }

    public function getWeekDates($year, $week)
    {
        $week = $week * 1;
        $week = $week < 10 ? "0" . $week : $week;
        $from = date("Y-m-d H:i:s", strtotime("{$year}-W{$week}-1")); //Returns the date of monday in week
        $to = date("Y-m-d H:i:s", strtotime("+23 hour +59 minutes +59 seconds", strtotime("{$year}-W{$week}-7"))); //Returns the date of sunday in week

        return array('from' => $from, 'to' => $to);
    }

    public function makeWeeks($dateTime)
    {
        $dt = self::makeDateTime($dateTime);
        $month = $dt->format("m");
        $day = $dt->format('d');
        $year = $dt->format('Y');
        $firstDayOfMonth = date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 01, $year));
        $lastDayOfMonth = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $dt->format('t'), $year));
        $firstDay = self::makeDateTime($firstDayOfMonth);
        $weekInYear = $firstDay->format('W');

        $weeks = array();

        $flag = true;

        while ($flag) {
            $dateRange = self::getWeekDates($year, $weekInYear);
            $firstDayInWeek = $dateRange['from'];
            $lastDayInWeek = $dateRange['to'];
            if (strtotime($firstDayInWeek) < strtotime($firstDayOfMonth)) {
                $dateRange['from'] = $firstDayOfMonth;
            }
            // check if over last day of the month
            if (strtotime($lastDayInWeek) >= strtotime($lastDayOfMonth)) {
                $flag = false; // stop loop when day over last day
                $dateRange['to'] = $lastDayOfMonth;
            }

            array_push($weeks, $dateRange);
            $weekInYear++;
        }
        return $weeks;
    }

    public function makeDateTime($string)
    {
        return new \DateTime($string, new \DateTimeZone('Australia/Sydney'));
    }

    public function getWeeklyReport($week, $use_history)
    {
        $startDate = $week['from'];
        $endDate = $week['to'];

        if ($use_history == 0) {
            $histDocket = HistDocket::where('hist_type', 2)->where('docket_date', '>', $startDate)->where('docket_date', '<=', $endDate)->first();
            if ($histDocket != null) {
                $sales = $histDocket->total_inc;
                $tx = $histDocket->docket_count;
            } else {
                $sales = Docket::where('docket_date', '>', $startDate)->where('docket_date', '<=', $endDate)->whereIn('transaction', ["SA", "IV"])->sum('total_inc');
                $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->count();

            }

        } else {
            $sales = Docket::where('docket_date', '>', $startDate)->where('docket_date', '<=', $endDate)->whereIn('transaction', ["SA", "IV"])->sum('total_inc');
            $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->count();

        }

        return array('sales' => $sales, 'tx' => $tx);
    }

    public function getReportByProduct($startDate, $endDate, $shop, $user)
    {

        # found database connect credentials
        $db_path_array = explode(';', $shop->db_path);
        $db_password = explode(';', $shop->db_password);

        $database_ip = explode('=', $db_path_array[0])[1];
        $database_name = explode('=', $db_path_array[1])[1];
        $username = explode('=', $db_password[0])[1];
        $password = explode('=', $db_password[1])[1];

        # generate group report for this shop
        // try {
        # connect to DB
        DB::purge('sqlsrv');

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $database_ip);
        \Config::set('database.connections.sqlsrv.username', $username);
        \Config::set('database.connections.sqlsrv.password', $password);
        \Config::set('database.connections.sqlsrv.database', $database_name);
        \Config::set('database.connections.sqlsrv.port', 1433);
        

        $docketLines = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Stock.stock_id', '>', 0)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->whereIn('Docket.transaction', ["SA", "IV"])
            ->selectRaw('Stock.stock_id,Stock.sell,sum(DocketLine.quantity) as quantity,sum(DocketLine.sell_inc * DocketLine.quantity) as amount')
            ->groupBy('Stock.stock_id', 'Stock.sell')
            ->orderBy('amount', 'desc')
            ->take(25)
            ->get();
        

        foreach ($docketLines as $docketLine) {
            $stock = Stock::find($docketLine->stock_id);
            $docketLine->name = $stock->description;

        }

        $ths = array(
            ['type' => 'text', 'value' => 'name'],
            ['type' => 'number', 'value' => 'quantity'],
            ['type' => 'number', 'value' => 'amount'],
        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'name'],
            ['type' => 'number', 'value' => 'quantity'],
            ['type' => 'number', 'value' => 'amount'],
        );
        $data = $docketLines;

        return compact('ths', 'dataFormat', 'data');
    }

    public function getReportByCategory($startDate, $endDate, $shop, $user)
    {

        # found database connect credentials
        $db_path_array = explode(';', $shop->db_path);
        $db_password = explode(';', $shop->db_password);

        $database_ip = explode('=', $db_path_array[0])[1];
        $database_name = explode('=', $db_path_array[1])[1];
        $username = explode('=', $db_password[0])[1];
        $password = explode('=', $db_password[1])[1];

        # generate group report for this shop
        // try {
        # connect to DB
        DB::purge('sqlsrv');

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $database_ip);
        \Config::set('database.connections.sqlsrv.username', $username);
        \Config::set('database.connections.sqlsrv.password', $password);
        \Config::set('database.connections.sqlsrv.database', $database_name);
        \Config::set('database.connections.sqlsrv.port', 1433);

       

        $categories = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Stock.stock_id', '>', 0)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->whereIn('Docket.transaction', ["SA", "IV"])
            ->selectRaw('Stock.cat1,sum(DocketLine.quantity) as quantity,sum(DocketLine.sell_inc * DocketLine.quantity) as amount')
            ->groupBy('cat1')
            ->orderBy('amount', 'desc')
            ->get();
        
        foreach ($categories as $category) {

            $category->name = $category->cat1;
        }

        $ths = array(
            ['type' => 'text', 'value' => 'name'],
            ['type' => 'number', 'value' => 'quantity'],
            ['type' => 'number', 'value' => 'amount'],
        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'name'],
            ['type' => 'number', 'value' => 'quantity'],
            ['type' => 'number', 'value' => 'amount'],
        );
        $data = $categories;

        return compact('ths', 'dataFormat', 'data');
    }

    public function getReportByDay($startDate, $endDate, $shop, $user_id)
    {

                # found database connect credentials
                $db_path_array = explode(';', $shop->db_path);
                $db_password = explode(';', $shop->db_password);
        
                $database_ip = explode('=', $db_path_array[0])[1];
                $database_name = explode('=', $db_path_array[1])[1];
                $username = explode('=', $db_password[0])[1];
                $password = explode('=', $db_password[1])[1];
        
                # generate group report for this shop
                // try {
                # connect to DB
                DB::purge('sqlsrv');
        
                // set connection database ip in run time
                \Config::set('database.connections.sqlsrv.host', $database_ip);
                \Config::set('database.connections.sqlsrv.username', $username);
                \Config::set('database.connections.sqlsrv.password', $password);
                \Config::set('database.connections.sqlsrv.database', $database_name);
                \Config::set('database.connections.sqlsrv.port', 1433);

        $dockets = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->select(DB::raw('CONVERT(VARCHAR(10), docket_date, 120) as date, gp,discount, total_inc'))->get();

        $ths = array(
            ['type' => 'text', 'value' => 'date'],
            ['type' => 'number', 'value' => 'amount'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'gp'],
        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'date'],
            ['type' => 'number', 'value' => 'amount'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'gp'],
        );

        $data = array();

        $docketGroups = $dockets->groupBy('date');

        foreach ($docketGroups as $key => $value) {
            $row['date'] = $key;
            $row['gp'] = collect($value)->sum('gp');
            $row['discount'] = collect($value)->sum('discount');
            $row['amount'] = collect($value)->sum('total_inc');

            array_push($data, $row);
        }

        // $sampleDocket = Docket::first();

        return compact('ths', 'dataFormat', 'data');

    }

    public function getReportByHour($startDate, $endDate, $shop, $user)
    {
                # found database connect credentials
                $db_path_array = explode(';', $shop->db_path);
                $db_password = explode(';', $shop->db_password);
        
                $database_ip = explode('=', $db_path_array[0])[1];
                $database_name = explode('=', $db_path_array[1])[1];
                $username = explode('=', $db_password[0])[1];
                $password = explode('=', $db_password[1])[1];
        
                # generate group report for this shop
                // try {
                # connect to DB
                DB::purge('sqlsrv');
        
                // set connection database ip in run time
                \Config::set('database.connections.sqlsrv.host', $database_ip);
                \Config::set('database.connections.sqlsrv.username', $username);
                \Config::set('database.connections.sqlsrv.password', $password);
                \Config::set('database.connections.sqlsrv.database', $database_name);
                \Config::set('database.connections.sqlsrv.port', 1433);

        $data = array();
        $ths = array(
            ['type' => 'text', 'value' => 'hour'],
            ['type' => 'number', 'value' => 'amount'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'gp'],
        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'hour'],
            ['type' => 'number', 'value' => 'amount'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'gp'],
        );


            $dockets = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->select(DB::raw('DATEPART(HOUR,docket_date) as hour, gp,discount, total_inc'))->orderBy('hour')->get();
            $docketGroups = $dockets->groupBy('hour');

            foreach ($docketGroups as $key => $value) {
                $row['hour'] = $key . ':00';
                $row['gp'] = collect($value)->sum('gp');
                $row['discount'] = collect($value)->sum('discount');
                $row['amount'] = collect($value)->sum('total_inc');

                array_push($data, $row);
            }
        

        return compact('ths', 'dataFormat', 'data');

    }


    public function getReportByStaff($startDate, $endDate, $shop, $user)
    {
            # found database connect credentials
            $db_path_array = explode(';', $shop->db_path);
            $db_password = explode(';', $shop->db_password);
    
            $database_ip = explode('=', $db_path_array[0])[1];
            $database_name = explode('=', $db_path_array[1])[1];
            $username = explode('=', $db_password[0])[1];
            $password = explode('=', $db_password[1])[1];
    
            # generate group report for this shop
            // try {
            # connect to DB
            DB::purge('sqlsrv');
    
            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $database_ip);
            \Config::set('database.connections.sqlsrv.username', $username);
            \Config::set('database.connections.sqlsrv.password', $password);
            \Config::set('database.connections.sqlsrv.database', $database_name);
            \Config::set('database.connections.sqlsrv.port', 1433);


            $data = DB::connection('sqlsrv')->table('DocketLine')
                ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
                ->join('staff', 'staff.staff_id', '=', 'Docket.staff_id')
                ->whereBetween('Docket.docket_date', [$startDate, $endDate])
                ->whereIn('Docket.transaction', ["SA", "IV"])
                ->selectRaw('staff.barcode,(staff.given_names + staff.surname) as groupName,count(DocketLine.line_id) as count,sum((DocketLine.sell_ex - DocketLine.cost_ex) * DocketLine.quantity) as gp , sum(DocketLine.sell_inc * DocketLine.quantity) as sales')
                ->groupBy('staff.barcode', 'staff.surname', 'staff.given_names')
                ->orderBy('sales', 'desc')
                ->get();
        
       
        $totalCount = 0;
        $totalSales = 0;
        $totalGp = 0;
        

        foreach ($data as $value) {
            $value->gp_percentage = $value->gp / ($value->sales == 0 ? 1 : $value->sales);
            $totalCount += $value->count;
            $totalSales += $value->sales;
            $totalGp += $value->gp;
        }

        $total = ['sales'=>$totalSales, 'count'=>$totalCount, 'gp'=>$totalGp,'gp_percentage'=>$totalGp/($totalSales==0?1:$totalSales)];

        return ["summary"=>$total,"details"=>$data];

    }

    

    public function getReportByCustomer($startDate, $endDate, $shop, $user)
    {
            # found database connect credentials
            $db_path_array = explode(';', $shop->db_path);
            $db_password = explode(';', $shop->db_password);
    
            $database_ip = explode('=', $db_path_array[0])[1];
            $database_name = explode('=', $db_path_array[1])[1];
            $username = explode('=', $db_password[0])[1];
            $password = explode('=', $db_password[1])[1];
    
            # generate group report for this shop
            // try {
            # connect to DB
            DB::purge('sqlsrv');
    
            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $database_ip);
            \Config::set('database.connections.sqlsrv.username', $username);
            \Config::set('database.connections.sqlsrv.password', $password);
            \Config::set('database.connections.sqlsrv.database', $database_name);
            \Config::set('database.connections.sqlsrv.port', 1433);


            $data = DB::connection('sqlsrv')->table('DocketLine')
                ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
                ->join('Customer', 'Customer.customer_id', '=', 'Docket.customer_id')
                ->whereBetween('Docket.docket_date', [$startDate, $endDate])
                ->whereIn('Docket.transaction', ["SA", "IV"])
                ->selectRaw('Customer.barcode,Customer.given_names as groupName,count(DISTINCT Docket.Docket_id) as count,sum((DocketLine.sell_ex - DocketLine.cost_ex) * DocketLine.quantity) as gp , sum(DocketLine.sell_inc * DocketLine.quantity) as sales')
                ->groupBy('Customer.barcode', 'Customer.surname', 'Customer.given_names')
                ->orderBy('sales', 'desc')
                ->get();
        
       
        $totalCount = 0;
        $totalSales = 0;
        $totalGp = 0;
        

        foreach ($data as $value) {
            $value->gp_percentage = $value->gp / ($value->sales == 0 ? 1 : $value->sales);
            $totalCount += $value->count;
            $totalSales += $value->sales;
            $totalGp += $value->gp;
        }

        $total = ['sales'=>$totalSales, 'count'=>$totalCount, 'gp'=>$totalGp,'gp_percentage'=>$totalGp/($totalSales==0?1:$totalSales)];

        return ["summary"=>$total,"details"=>$data];

    }

    # self functions
    public function getShopSummary($startDate, $endDate, $shop)
    {
        # code...
        # found database connect credentials
        $db_path_array = explode(';', $shop->db_path);
        $db_password = explode(';', $shop->db_password);

        $database_ip = explode('=', $db_path_array[0])[1];
        $database_name = explode('=', $db_path_array[1])[1];
        $username = explode('=', $db_password[0])[1];
        $password = explode('=', $db_password[1])[1];

        try {
            # connect to DB
            DB::purge('sqlsrv');

            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $database_ip);
            \Config::set('database.connections.sqlsrv.username', $username);
            \Config::set('database.connections.sqlsrv.password', $password);
            \Config::set('database.connections.sqlsrv.database', $database_name);
            \Config::set('database.connections.sqlsrv.port', 1433);
            # get connection info

            $sqlResult = DB::connection('sqlsrv')->table('DocketLine')
                ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            // ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            // ->where('Stock.stock_id', '>', 0)
                ->whereBetween('Docket.docket_date', [$startDate, $endDate])
                ->whereIn('Docket.transaction', ["SA", "IV"])
                ->selectRaw('sum((DocketLine.sell_ex - DocketLine.cost_ex) * DocketLine.quantity) as gp ,sum(DocketLine.RRP - DocketLine.sell_inc) as discount,count(DISTINCT Docket.Docket_id) as totalTx,sum(DocketLine.sell_inc * DocketLine.quantity) as totalSales,sum(abs(DocketLine.sell_inc * DocketLine.quantity)) as absTotal,sum(DocketLine.sell_ex * DocketLine.quantity) as totalSales_ex,sum(DocketLine.sell_inc - DocketLine.sell_ex) as gst')
                ->first();

            # calculate totalRefund
            $sqlResult->totalRefund = ($sqlResult->totalSales - $sqlResult->absTotal) / 2;
            $sqlResult2 = DB::connection('sqlsrv')->table('DocketLine')
                ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            // ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            // ->where('Stock.stock_id', '>', 0)
                ->whereBetween('Docket.docket_date', [$startDate, $endDate])
                ->whereIn('Docket.transaction', ["SA", "IV"])
                ->where('DocketLine.quantity', '<', 0)
                ->selectRaw('sum(DocketLine.quantity) as refundQty')
                ->first();
            // ->get();

            # calculate gp_percentage
            $sqlResult->gp_percentage = $sqlResult->totalSales_ex != 0 ? $sqlResult->gp / $sqlResult->totalSales_ex : 0;
            return [
                'totalSales' => $sqlResult->totalSales,
                'totalTx' => $sqlResult->totalTx,
                'shop' => $shop,
                'gp' => $sqlResult->gp == null ? 0 : $sqlResult->gp,
                'discount' => $sqlResult->discount == null ? 0 : $sqlResult->discount,
                'gp_percentage' => $sqlResult->gp_percentage,
                'gst' => $sqlResult->gst,
                'totalRefund' => $sqlResult->totalRefund,
                'refundQty' => $sqlResult2->refundQty,
                'connected' => true,

            ];
        } catch (\Throwable $th) {
            return [
                'totalSales' => 0,
                'totalTx' => 0,
                'shop' => $shop,
                'gp' => 0,
                'discount' => 0,
                'gp_percentage' => 0,
                'gst' => 0,
                'totalRefund' => 0,
                'refundQty' => 0,
                'connected' => false,
            ];
        }

        // $shops = DB::connection('sqlsrv')->table('Docket')->whereBetween('docket_date', [$startDate, $endDate])->sum('total_inc');
        // return $shops;

    }

    /**
     * 生成单个旅行团的报表
     */
    public function getGroupReports($startDate, $endDate, $shop,$groupId,$group_name)
    {
                # found database connect credentials
                $db_path_array = explode(';', $shop->db_path);
                $db_password = explode(';', $shop->db_password);
        
                $database_ip = explode('=', $db_path_array[0])[1];
                $database_name = explode('=', $db_path_array[1])[1];
                $username = explode('=', $db_password[0])[1];
                $password = explode('=', $db_password[1])[1];

        # generate group report for this shop
        // try {
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
                ->join('Customer','Docket.customer_id','=','Customer.customer_id')
                // ->where('Docket.customer_id',$customer_id)
                ->where('Customer.given_names',$group_name)
                ->selectRaw('Customer.customer_id,sum(DocketLine.sell_inc * DocketLine.quantity) as totalSales')
                ->groupBy('Customer.customer_id')
                ->first();
            
                return $sqlResult;
        // } catch (\Throwable $th) {
        //     //throw $th;
        //     return [];
        // }
    }

    /**
     * 生成旅行社的报表
     */
    public function getAgentReport($startDate, $endDate, $shop,$agentName,$groupNames)
    {
                # found database connect credentials
                $db_path_array = explode(';', $shop->db_path);
                $db_password = explode(';', $shop->db_password);
                $database_ip = explode('=', $db_path_array[0])[1];
                $database_name = explode('=', $db_path_array[1])[1];
                $username = explode('=', $db_password[0])[1];
                $password = explode('=', $db_password[1])[1];
        
                # generate group report for this shop
                try {
                    # connect to DB
                    DB::purge('sqlsrv');
                    // set connection database ip in run time
                    \Config::set('database.connections.sqlsrv.host', $database_ip);
                    \Config::set('database.connections.sqlsrv.username', $username);
                    \Config::set('database.connections.sqlsrv.password', $password);
                    \Config::set('database.connections.sqlsrv.database', $database_name);
                    \Config::set('database.connections.sqlsrv.port', 1433); 

                    if($agentName==""){
                        $sqlResult = DB::connection('sqlsrv')->table('Docket')
                                        ->join('Customer','Docket.customer_id','=','Customer.customer_id')
                                        ->whereBetween('Customer.date_modified',[$startDate,$endDate])
                                        ->whereIn('Customer.given_names',$groupNames)
                                        ->selectRaw('Customer.given_names as groupName,Customer.surname as agentName,Customer.grade as kb,Customer.addr3 as guide,sum(Docket.total_inc) as totalSales')
                                        ->groupBy('Customer.given_names','Customer.surname','Customer.suburb','Customer.grade','Customer.addr3')
                                        ->get();
                    }else{
                        $sqlResult = DB::connection('sqlsrv')->table('Docket')
                                        ->join('Customer','Docket.customer_id','=','Customer.customer_id')
                                        ->whereBetween('Customer.date_modified',[$startDate,$endDate])
                                        ->where('Customer.surname',$agentName)
                                        ->whereIn('Customer.given_names',$groupNames)
                                        ->selectRaw('Customer.given_names as groupName,Customer.surname as agentName,Customer.grade as kb,Customer.addr3 as guide,sum(Docket.total_inc) as totalSales')
                                        ->groupBy('Customer.given_names','Customer.surname','Customer.suburb','Customer.grade','Customer.addr3')
                                        ->get();
                    }
                    foreach ($sqlResult as $ele) {
                        $ele->pax = 0;
                    }
                    #, Customer.suburb as pax

                }catch (\Throwable $th){
                    $sqlResult = [];
                }
                     
                    # generate readable shop summary report
                    return ["shopName"=>$shop->barcode,"reports"=>$sqlResult];
    }
}
