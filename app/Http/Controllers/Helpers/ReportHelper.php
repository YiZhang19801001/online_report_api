<?php
namespace App\Http\Controllers\Helpers;

use App\Docket;
use App\Payments;
use App\Stock;
use \Illuminate\Support\Facades\DB;

class ReportHelper
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
    public function getDailySummary($dateTime)
    {
        $dt = self::makeDateTime($dateTime);
        $date = $dt->format('Y-m-d');
        $stopDate = date('Y-m-d', strtotime($date . '+1 day'));
        $yesterday = date('Y-m-d', strtotime($date . '-1 day'));

        $sql = Docket::whereBetween('docket_date', [$date, $stopDate])->where('transaction', "SA")->orWhere('transaction', "IV");
        $compareSql = Docket::whereBetween('docket_date', [$yesterday, $date])->where('transaction', "SA")->orWhere('transaction', "IV");

        $sales = $sql->sum('total_inc');
        $compareSales = $compareSql->sum('total_inc');
        $numberOfTransactions = $sql->count();
        $compareNumberOfTransactions = $compareSql->count();
        $reportsForPaymentMethod = self::reportsForPaymentMethod($date, $stopDate);

        return compact('date', 'stopDate', 'sales', 'compareSales', 'compareNumberOfTransactions', 'numberOfTransactions', 'reportsForPaymentMethod');
    }

    public function getWeeklySummary($dateTime)
    {
        //todo:: clean up duplication codes in self::getWeeklyReport()
        $dtString = date('Y-m-d', strtotime($dateTime . '-1 month'));

        $dt = self::makeDateTime($dtString);
        $month = $dt->format('m');
        $year = $dt->format('Y');
        $startDate = date('Y-m-d', mktime(0, 0, 0, $month, 01, $year));
        $endDate = date('Y-m-d', mktime(0, 0, 0, $month, $dt->format('t'), $year));
        $sales = Docket::whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV")->sum('total_inc');
        $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV")->count();
        $comparison = ['date' => $startDate, 'sales' => $sales, 'tx' => $tx];

        $weeklyReports = array();
        $weeks = self::makeWeeks($dateTime);
        foreach ($weeks as $week) {
            $report = self::getWeeklyReport($week);
            $report['from'] = $week['from'];
            $report['to'] = $week['to'];
            $report['paymentMethodReports'] = self::reportsForPaymentMethod($week['from'], $week['to']);
            array_push($weeklyReports, $report);
        }
        return compact('weeklyReports', 'weeks', 'comparison');
    }

    public function reportsForPaymentMethod($start, $end)
    {
        $sql = Payments::whereBetween('docket_date', [$start, $end]);
        $sum = $sql->sum('amount');
        $groups = $sql
            ->selectRaw("sum(amount) as total, paymenttype, ROUND(sum(amount)/$sum,2) as percentage")
            ->groupBy('paymenttype')
            ->get();
        return $groups;
    }

    public function getDataGroup($dateTime)
    {

        $dt = new \DateTime($dateTime, new \DateTimeZone('Australia/Sydney'));
        $startDate = $dt->format('Y-m-d');
        $endDate = date('Y-m-d', strtotime($startDate . '+1 day'));

        $dataGroup = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Stock.stock_id', '>', 0)
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

    public function getTotalSummary($shops, $startDate, $endDate)
    {
        $reports = [];
        foreach ($shops as $shop) {
            $report = $this->makeReport($shop, $startDate, $endDate);
            array_push($reports, $report);
        }

        return collect($reports)->values();
    }

    public function makeReport($shop, $startDate, $endDate)
    {
        DB::purge('sqlsrv');

        // set connection database ip in run time
        \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
        \Config::set('database.connections.sqlsrv.username', $shop->username);
        \Config::set('database.connections.sqlsrv.password', $shop->password);
        \Config::set('database.connections.sqlsrv.database', $shop->database_name);
        \Config::set('database.connections.sqlsrv.port', $shop->port);

        # read all dockets during the period
        $sql = Docket::whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV");
        $totalSales = $sql->sum('total_inc');
        $totalRefund = 0;
        foreach ($sql->get() as $item) {
            if ($item->total_inc < 0) {
                $totalRefund += $item->total_inc;
            }
        }
        $sqlResult = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Stock.stock_id', '>', 0)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->where('Docket.transaction', "SA")->orWhere('Docket.transaction', "IV")
            ->selectRaw('sum((DocketLine.sell_ex - DocketLine.cost_ex) * DocketLine.quantity) as gp ,sum(DocketLine.RRP - DocketLine.sell_inc) as discount')
            ->first();

        return ['totalSales' => $totalSales, 'totalTx' => $sql->count(), 'shop' => $shop, 'gp' => $sqlResult->gp, 'discount' => $sqlResult->discount, 'gp_percentage' => $sqlResult->gp / $totalSales, 'totalRefund' => $totalRefund];
        // return DocketLine::first();

    }

    public function weekOfMonth($date)
    {
        //Get the first day of the month.
        $firstOfMonthString = $date->format("Y-m-01");

        //Apply above formula.
        return $date->format('w') + 1 - $firstOfMonth->format('w');
    }

    public function getWeekDates($year, $week)
    {
        $from = date("Y-m-d", strtotime("{$year}-W{$week}-1")); //Returns the date of monday in week
        $to = date("Y-m-d", strtotime("{$year}-W{$week}-7")); //Returns the date of sunday in week

        return array('from' => $from, 'to' => $to);
    }

    public function makeWeeks($dateTime)
    {
        $dt = self::makeDateTime($dateTime);
        $month = $dt->format("m");
        $day = $dt->format('d');
        $year = $dt->format('Y');
        $firstDayOfMonth = date('Y-m-d', mktime(0, 0, 0, $month, 01, $year));
        $lastDayOfMonth = date('Y-m-d', mktime(0, 0, 0, $month, $dt->format('t'), $year));
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

    public function getWeeklyReport($week)
    {
        $startDate = $week['from'];
        $endDate = $week['to'];

        $sales = Docket::whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV")->sum('total_inc');
        $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV")->count();

        return array('sales' => $sales, 'tx' => $tx);
    }

    public function getReportByProduct($startDate, $endDate)
    {
        $docketLines = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Stock.stock_id', '>', 0)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->where('Docket.transaction', "SA")->orWhere('Docket.transaction', "IV")
            ->selectRaw('Stock.stock_id,sum(DocketLine.quantity) as quantity,sum(DocketLine.sell_inc) as amount')
            ->groupBy('Stock.stock_id')
            ->take(15)
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
        // $sampleDocketLine = DocketLine::whereIn('docket_id', $docketIds)->first();

        return compact('ths', 'dataFormat', 'data');
    }

    public function getReportByCategory($startDate, $endDate)
    {
        $categories = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Stock.stock_id', '>', 0)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->where('Docket.transaction', "SA")->orWhere('Docket.transaction', "IV")
            ->selectRaw('Stock.cat1,sum(DocketLine.quantity) as quantity,sum(DocketLine.sell_inc) as amount')
            ->groupBy('cat1')
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
        // $sampleDocketLine = DocketLine::whereIn('docket_id', $docketIds)->first();
        // $sampleStock = Stock::first();

        return compact('ths', 'dataFormat', 'data');
    }

    public function getReportByDay($startDate, $endDate)
    {
        $dockets = Docket::whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV")->select(DB::raw('CONVERT(VARCHAR(10), docket_date, 120) as date, gp,discount, total_inc'))->get();

        $ths = array(
            ['type' => 'text', 'value' => 'date'],
            ['type' => 'number', 'value' => 'gp'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'amount'],
        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'date'],
            ['type' => 'number', 'value' => 'gp'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'amount'],
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

    public function getReportByHour($startDate, $endDate)
    {

        $dockets = Docket::whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV")->select(DB::raw('DATEPART(HOUR,docket_date) as hour, gp,discount, total_inc'))->orderBy('hour')->get();

        $ths = array(
            ['type' => 'text', 'value' => 'hour'],
            ['type' => 'number', 'value' => 'gp'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'amount'],
        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'hour'],
            ['type' => 'number', 'value' => 'gp'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'amount'],
        );

        $data = array();

        $docketGroups = $dockets->groupBy('hour');

        foreach ($docketGroups as $key => $value) {
            $row['hour'] = $key . ':00';
            $row['gp'] = collect($value)->sum('gp');
            $row['discount'] = collect($value)->sum('discount');
            $row['amount'] = collect($value)->sum('total_inc');

            array_push($data, $row);
        }

        // $sampleDocket = Docket::first();

        return compact('ths', 'dataFormat', 'data');

    }

    public function getReportByCustomer($startDate, $endDate)
    {

        $ths = array(
            ['type' => 'text', 'value' => 'id'],
            ['type' => 'number', 'value' => 'gp'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'amount'],
            ['type' => 'number', 'value' => 'gp%'],

        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'customer_id'],
            ['type' => 'number', 'value' => 'gp'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'amount'],
            ['type' => 'number', 'value' => 'gp_percentage'],

        );

        # read all dockets during the period
        $sql = Docket::whereBetween('docket_date', [$startDate, $endDate])->where('transaction', "SA")->orWhere('transaction', "IV");
        $totalSales = $sql->sum('total_inc');
        $totalRefund = 0;
        foreach ($sql->get() as $item) {
            if ($item->total_inc < 0) {
                $totalRefund += $item->total_inc;
            }
        }

        $data = DB::connection('sqlsrv')->table('DocketLine')
            ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
            ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
            ->where('Stock.stock_id', '>', 0)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->where('Docket.transaction', "SA")->orWhere('Docket.transaction', "IV")
            ->selectRaw('Docket.customer_id,sum((DocketLine.sell_ex - DocketLine.cost_ex) * DocketLine.quantity) as gp ,sum(DocketLine.RRP - DocketLine.sell_inc) as discount, sum(Docket.total_inc) as amount')
            ->groupBy('Docket.customer_id')
            ->get();

        foreach ($data as $value) {
            $value->gp_percentage = $value->gp / ($value->amount == 0 ? 1 : $value->amount);
        }

        return compact('ths', 'dataFormat', 'data');

    }
}
