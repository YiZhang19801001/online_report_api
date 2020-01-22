<?php
namespace App\Http\Controllers\Helpers;

use App\Docket;
use App\HistDocket;
use App\HistPayments;
use App\Payments;
use App\Stock;
use \Illuminate\Support\Facades\DB;

class PosReportHelper
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
    public function getDailySummary($dateTime, $user)
    {

        #read inputs & manuplate data
        $dt = self::makeDateTime($dateTime);
        $date = $dt->format('Y-m-d');
        $stopDate = date('Y-m-d', strtotime($date . '+1 day'));
        $yesterday = date('Y-m-d', strtotime($date . '-1 day'));

        # CONDITION: use_history === true --> generate reports from history table
        if ($user->use_history == 0) {
            # try to find history record (will try to get those)
            $histDocket = HistDocket::whereBetween('docket_date', [$date, $stopDate])->where('hist_type', 1)->first();
            $compareHistDocket = HistDocket::whereBetween('docket_date', [$yesterday, $date])->where('hist_type', 1)->first();

            # if history record was found, read figures straight away from record
            if ($histDocket !== null) {

                $sales = $histDocket->total_inc;
                $numberOfTransactions = $histDocket->docket_count;
                $reportsForPaymentMethod = HistPayments::whereBetween('docket_date', [$date, $stopDate])
                    ->selectRaw("sum(amount) as total, paymenttype")
                    ->groupBy('paymenttype')
                    ->get();
                $resource = "use_history"; // todo:: remove this line
            } else { #else select figures from [Docket] table
            $sql = Docket::whereBetween('docket_date', [$date, $stopDate])->whereIn('transaction', ["SA", "IV"]);
                $sales = $sql->sum('total_inc');
                $numberOfTransactions = $sql->count();
                $reportsForPaymentMethod = self::reportsForPaymentMethod($date, $stopDate);

            }

            # if history record was found, read figures straight away from record
            if ($compareHistDocket !== null) {
                $compareSales = $compareHistDocket->total_inc;
                $compareNumberOfTransactions = $compareHistDocket->docket_count;

            } else { #else select figures from [Docket] table
            $compareSql = Docket::whereBetween('docket_date', [$yesterday, $date])->whereIn('transaction', ["SA", "IV"]);
                $compareSales = $compareSql->sum('total_inc');
                $compareNumberOfTransactions = $compareSql->count();
            }

        } else {
            # CONDITION: use_history === false --> generate reports by selecting data from [Docket] [DocketLine] [Payments]
            $sql = Docket::whereBetween('docket_date', [$date, $stopDate])->whereIn('transaction', ["SA", "IV"]);
            $compareSql = Docket::whereBetween('docket_date', [$yesterday, $date])->whereIn('transaction', ["SA", "IV"]);

            $sales = $sql->sum('total_inc');
            $numberOfTransactions = $sql->count();

            $compareSales = $compareSql->sum('total_inc');
            $compareNumberOfTransactions = $compareSql->count();

            $reportsForPaymentMethod = self::reportsForPaymentMethod($date, $stopDate);
            $resource = "not_use_history";
        }

        return compact('date', 'stopDate', 'sales', 'compareSales', 'compareNumberOfTransactions', 'numberOfTransactions', 'reportsForPaymentMethod', "resource", "histDocket");
    }

    public function getWeeklySummary($dateTime, $user)
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

        # CONDITION: use_history === true --> generate reports from history table
        if ($user->use_history == 0) {
            # try to find weely report
            $histDocket = HistDocket::whereBetween('docket_date', [$startDate, $endDate])->where('hist_type', 3)->first();
            if ($histDocket != null) {
                $comparison = ['date' => $startDate, 'sales' => $histDocket->total_inc, 'tx' => $histDocket->docket_count];
                foreach ($weeks as $week) {
                    $report = self::getWeeklyReport($week, $user->use_history);
                    $report['from'] = $week['from'];
                    $report['to'] = $week['to'];
                    $report['paymentMethodReports'] = self::reportsForPaymentMethod($week['from'], $week['to']);
                    array_push($weeklyReports, $report);
                }
            } else {
                $sales = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->sum('total_inc');
                $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->count();
                $comparison = ['date' => $startDate, 'sales' => $sales, 'tx' => $tx];
                foreach ($weeks as $week) {
                    $report = self::getWeeklyReport($week, $user->use_history);
                    $report['from'] = $week['from'];
                    $report['to'] = $week['to'];
                    $report['paymentMethodReports'] = self::reportsForPaymentMethod($week['from'], $week['to']);
                    array_push($weeklyReports, $report);
                }
            }
        } else {
            $sales = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->sum('total_inc');
            $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->count();
            $comparison = ['date' => $startDate, 'sales' => $sales, 'tx' => $tx];
            foreach ($weeks as $week) {
                $report = self::getWeeklyReport($week, $user->use_history);
                $report['from'] = $week['from'];
                $report['to'] = $week['to'];
                $report['paymentMethodReports'] = self::reportsForPaymentMethod($week['from'], $week['to']);
                array_push($weeklyReports, $report);
            }
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

    public function getTotalSummary($shops, $startDate, $endDate, $user)
    {
        try {
            $reports = [];
            $newShops = [];
            foreach ($shops as $shop) {
                $report = $this->makeReport($shop, $startDate, $endDate, $user);
                if ($report != "") {
                    array_push($reports, $report);
                    array_push($newShops, $shop);
                } else {
                    array_push($reports, [
                        'totalSales' => null,
                        'totalTx' => null,
                        'shop' => $shop,
                        'gp' => null,
                        'discount' => null,
                        'gp_percentage' => null,
                        'totalRefund' => null,
                        'toRefund' => null,
                    ]);
                }
            }

            return ['reports' => collect($reports)->values(), 'shops' => $newShops];
        } catch (\Throwable $th) {

            $reports = [];
            $newShops = [];
            foreach ($shops as $shop) {
                array_push($reports, [
                    'totalSales' => null,
                    'totalTx' => null,
                    'shop' => $shop,
                    'gp' => null,
                    'discount' => null,
                    'gp_percentage' => null,
                    'totalRefund' => null,
                    'toRefund' => null,
                ]);

            }

            return ['reports' => collect($reports)->values(), 'shops' => $newShops];

        }
    }

    public function makeReport($shop, $startDate, $endDate, $user)
    {
        try {
            set_time_limit(30);
            // init_set('mssql.timeout', 3);
            DB::purge('sqlsrv');

            // set connection database ip in run time
            \Config::set('database.connections.sqlsrv.host', $shop->database_ip);
            \Config::set('database.connections.sqlsrv.username', $shop->username);
            \Config::set('database.connections.sqlsrv.password', $shop->password);
            \Config::set('database.connections.sqlsrv.database', $shop->database_name);
            \Config::set('database.connections.sqlsrv.port', $shop->port);

            if ($user->use_history == 0) {
                $sqlResult = HistDocket::whereBetween('docketDate', [$startDate, $endDate])
                    ->where('hist_type', 1)
                    ->selectRaw('sum(gp) as gp ,sum(discount) as discount,sum(docket_count) as totalTx,sum(total_inc) as totalSales,sum(refund) as totalRefund,,sum(total_ex) as totalSales_ex')
                    ->first();

                return [
                    'totalSales' => $sqlResult->totalSales,
                    'totalTx' => $sqlResult->totalTx,
                    'shop' => $shop,
                    'gp' => $sqlResult->gp == null ? 0 : $sqlResult->gp,
                    'discount' => $sqlResult->discount == null ? 0 : $sqlResult->discount,
                    'gp_percentage' => ($sqlResult->totalSales_ex == 0 || !$sqlResult->totalSales_ex) ? 0 : $sqlResult->gp_percentage / $sqlResult->totalSales_ex,
                    'totalRefund' => $sqlResult->totalRefund,
                ];
            } else {
                # read all dockets during the period
                $sqlResult = DB::connection('sqlsrv')->table('DocketLine')
                    ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
                    ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
                    ->where('Stock.stock_id', '>', 0)
                    ->whereBetween('Docket.docket_date', [$startDate, $endDate])
                    ->whereIn('Docket.transaction', ["SA", "IV"])
                    ->whereIn('transaction', ["SA", "IV"])
                    ->selectRaw('sum((DocketLine.sell_ex - DocketLine.cost_ex) * DocketLine.quantity) as gp ,sum((DocketLine.RRP - DocketLine.sell_inc)*DocketLine.quantity) as discount,count(DISTINCT Docket.Docket_id) as totalTx,sum(DocketLine.sell_inc * DocketLine.quantity) as totalSales,sum(abs(DocketLine.sell_inc * DocketLine.quantity)) as absTotal,sum(DocketLine.sell_ex * DocketLine.quantity) as totalSales_ex')
                    ->first();

                # calculate totalRefund
                $sqlResult2 = DB::connection('sqlsrv')->table('DocketLine')
                    ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
                    ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
                    ->where('Stock.stock_id', '>', 0)
                    ->whereBetween('Docket.docket_date', [$startDate, $endDate])
                    ->whereIn('Docket.transaction', ["SA", "IV"])
                    ->where('DocketLine.quantity', '<', 0)
                    ->selectRaw('sum(DocketLine.quantity) * -1 as refundQty,sum(DocketLine.quantity * DocketLine.sell_inc) * -1 as totalRefund')
                    ->first();

                # calculate gp_percentage
                $sqlResult->gp_percentage = ($sqlResult->totalSales_ex != 0 && $sqlResult->totalSales_ex) ? $sqlResult->gp / $sqlResult->totalSales_ex : 0;
                return [
                    'totalSales' => $sqlResult->totalSales,
                    'totalTx' => $sqlResult->totalTx,
                    'shop' => $shop,
                    'gp' => $sqlResult->gp == null ? 0 : $sqlResult->gp,
                    'discount' => $sqlResult->discount == null ? 0 : $sqlResult->discount,
                    'gp_percentage' => $sqlResult->gp_percentage,
                    'totalRefund' => $sqlResult2->totalRefund,
                ];
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }

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

        # CONDITION: $use_history true --> try to get weekly report from histDocket
        if ($use_history == 0) {
            $histDocket = HistDocket::whereBetween('docket_date', [$startDate, $endDate])->where('hist_type', 2)->first();
            if ($histDocket != null) {
                $sales = $histDocket->total_inc;
                $tx = $histDocket->docket_count;
            } else {
                $sales = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->sum('total_inc');
                $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->count();
            }
        } else {

            $sales = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->sum('total_inc');
            $tx = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->count();
        }

        return array('sales' => $sales, 'tx' => $tx);
    }

    public function getReportByProduct($startDate, $endDate, $user)
    {
        if ($user->use_history == 0) {
            $docketLines = HistDocketLine::whereBetween('docket_date', [$startDate, $endDate])
                ->where('hist_type', 1)
                ->where('stock_id', '>', 0)
                ->selectRaw('stock_id,sum(quantity) as quantity,sum(sell_inc) as amount')
                ->groupBy('stock_id')
                ->take(15)
                ->get();

        } else {
            $docketLines = DB::connection('sqlsrv')->table('DocketLine')
                ->join('Docket', 'DocketLine.docket_id', '=', 'Docket.docket_id')
                ->join('Stock', 'Stock.stock_id', '=', 'DocketLine.stock_id')
                ->where('Stock.stock_id', '>', 0)
                ->whereBetween('Docket.docket_date', [$startDate, $endDate])
                ->whereIn('Docket.transaction', ["SA", "IV"])
                ->selectRaw('Stock.stock_id,Stock.cost,sum(DocketLine.quantity) as quantity,sum(DocketLine.sell_inc * DocketLine.quantity) as amount')
                ->groupBy('Stock.stock_id', 'Stock.cost')
                ->orderBy('amount', 'desc')
                ->take(15)
                ->get();

        }
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

    public function getReportByCategory($startDate, $endDate, $user)
    {
        if ($user->use_history == 0) {
            $categories = DB::connection('sqlsrv')->table('HistDocketLine')
                ->join('Stock', 'Stock.stock_id', '=', 'HistDocketLine.stock_id')
                ->where('Stock.stock_id', '>', 0)
                ->whereBetween('HistDocketLine.docket_date', [$startDate, $endDate])
                ->selectRaw('Stock.cat1,sum(HistDocketLine.quantity) as quantity,sum(HistDocketLine.sell_inc * HistDocketLine.quantity) as amount')
                ->groupBy('cat1')
                ->get();
        } else {
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
        }

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

    public function getReportByDay($startDate, $endDate, $user)
    {
        $dockets = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->select(DB::raw('CONVERT(VARCHAR(10), docket_date, 120) as date, gp,discount, total_inc'))->get();
        $ths = array(
            ['type' => 'text', 'value' => 'date'],
            ['type' => 'number', 'value' => 'amount'],

        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'date'],
            ['type' => 'number', 'value' => 'amount'],

        );

        $data = array();

        $docketGroups = $dockets->groupBy('date');

        $groups = DB::connection('sqlsrv')->table('Payments')
            ->join('Docket', 'Payments.docket_id', '=', 'Docket.docket_id')
        // ->where('Docket.shop_id', $shopId)
            ->whereBetween('Docket.docket_date', [$startDate, $endDate])
            ->whereIn('Docket.transaction', ["SA", "IV"])
            ->selectRaw('CONVERT(VARCHAR(10), Docket.docket_date, 120) as date,Docket.gp as gp, Docket.discount as discount, Docket.total_inc as total_inc, Payments.paymenttype as paymenttype,Payments.amount as payment_amount')
            ->get();

        $groupedGroups = $groups->groupBy('date');

        foreach ($docketGroups as $key => $value) {
            $row['date'] = $key;
            $row['gp'] = collect($value)->sum('gp');
            $row['discount'] = collect($value)->sum('discount');
            $row['amount'] = collect($value)->sum('total_inc');
            foreach ($groupedGroups as $key2 => $value2) {
                if ($key2 == $key) {
                    $mediaReports = collect($value2)->groupBy('paymenttype');
                    // add paymenttype to $ths
                    foreach ($mediaReports as $key3 => $value3) {
                        if (!in_array(['type' => 'money', 'value' => $key3], $ths)) {
                            // if ths not contain this paymenttype add it first
                            array_push($ths, ['type' => 'money', 'value' => $key3]);
                            array_push($dataFormat, ['type' => 'money', 'value' => $key3]);
                            $row[$key3] = collect($value3)->sum('payment_amount');
                        } else {
                            //if ths has contained this paymenttype just add value to certain day report
                            $row[$key3] = collect($value3)->sum('payment_amount');
                        }
                    }
                }
            }
            array_push($data, $row);
        }

        // $sampleDocket = Docket::first();

        array_push($ths, ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'gp']);
        array_push($dataFormat, ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'gp']);

        return compact('ths', 'dataFormat', 'data');

    }

    public function getReportByHour($startDate, $endDate, $user)
    {

        if ($user->use_history == 0) {
            $dockets = HistDocket::where('hist_type', 0)->whereBetween('docket_date', [$startDate, $endDate])->select(DB::raw('DATEPART(HOUR,docket_date) as hour, gp,discount, total_inc'))->orderBy('hour')->get();

            $data = array();

            $docketGroups = $dockets->groupBy('hour');

            foreach ($docketGroups as $key => $value) {
                $row['hour'] = $key . ':00';
                $row['gp'] = collect($value)->sum('gp');
                $row['discount'] = collect($value)->sum('discount');
                $row['amount'] = collect($value)->sum('total_inc');
                array_push($data, $row);
            }
        } else {
            $dockets = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"])->select(DB::raw('DATEPART(HOUR,docket_date) as hour, gp,discount, total_inc'))->orderBy('hour')->get();

            $data = array();

            $docketGroups = $dockets->groupBy('hour');

            foreach ($docketGroups as $key => $value) {
                $row['hour'] = $key . ':00';
                $row['gp'] = collect($value)->sum('gp');
                $row['discount'] = collect($value)->sum('discount');
                $row['amount'] = collect($value)->sum('total_inc');

                array_push($data, $row);
            }
        }

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

        return compact('ths', 'dataFormat', 'data');

    }

    public function getReportByCustomer($startDate, $endDate, $user)
    {

        $ths = array(
            ['type' => 'text', 'value' => 'id'],
            ['type' => 'text', 'value' => 'name'],
            ['type' => 'number', 'value' => 'amount'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'gp'],
            ['type' => 'number', 'value' => 'gp%'],

        );
        $dataFormat = array(
            ['type' => 'text', 'value' => 'customer_id'],
            ['type' => 'text', 'value' => 'full_name'],
            ['type' => 'number', 'value' => 'amount'],
            ['type' => 'number', 'value' => 'discount'],
            ['type' => 'number', 'value' => 'gp'],
            ['type' => 'number', 'value' => 'gp_percentage'],

        );

        if ($user->use_history == 0) {
            $data = HistDocket::where('hist_type', 1)
                ->whereBetween('docket_date', [$startDate, $endDate])
                ->selectRaw('customer_id,surname, sum(gp) as gp, sum(discount) as discount, sum(total_inc) as amount')
                ->groupBy('customer_id', 'surname')
                ->get();

        } else {
            # read all dockets during the period
            $sql = Docket::whereBetween('docket_date', [$startDate, $endDate])->whereIn('transaction', ["SA", "IV"]);
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
                ->join('Customer', 'Customer.customer_id', '=', 'Docket.customer_id')
                ->where('Stock.stock_id', '>', 0)
                ->whereBetween('Docket.docket_date', [$startDate, $endDate])
                ->whereIn('Docket.transaction', ["SA", "IV"])
                ->selectRaw('Customer.customer_id,(Customer.surname + Customer.given_names) as full_name,sum((DocketLine.sell_ex - DocketLine.cost_ex) * DocketLine.quantity) as gp ,sum(DocketLine.RRP - DocketLine.sell_inc) as discount, sum(DocketLine.sell_inc * DocketLine.quantity) as amount,sum(DocketLine.sell_ex * DocketLine.quantity) as amount_ex')
                ->groupBy('Customer.customer_id', 'Customer.surname', 'Customer.given_names')
                ->orderBy('amount', 'desc')
                ->get();
        }

        foreach ($data as $value) {
            $value->gp_percentage = $value->gp / (($value->amount_ex == 0 || !$value->amount_ex) ? 1 : $value->amount_ex);
        }

        return compact('ths', 'dataFormat', 'data');

    }

    /**
     * function - generate summary report for single shop use for step loading in loading page
     *
     */
    public function getSingleShopReport($shop, $startDate, $endDate, $user)
    {
        try {
            $result = $this->makeReport($shop, $startDate, $endDate, $user);
            if ($result == "") {
                $report = [
                    'totalSales' => null,
                    'totalTx' => null,
                    'shop' => $shop,
                    'gp' => null,
                    'discount' => null,
                    'gp_percentage' => null,
                    'totalRefund' => null,
                    'toRefund' => null,
                ];
            } else {
                $report = $result;
            }

            return $report;

        } catch (\Throwable $th) {

            $report = [
                'totalSales' => null,
                'totalTx' => null,
                'shop' => $shop,
                'gp' => null,
                'discount' => null,
                'gp_percentage' => null,
                'totalRefund' => null,
                'toRefund' => null,
                'errMessage' => $th->getMessage(),
            ];

            return $report;
        }
    }
}
