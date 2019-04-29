<?php
namespace App\Http\Controllers\Helpers;

use App\Docket;
use App\Payments;
use App\Stock;

class ReportHelper
{
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

        $weeks = self::makeWeeks($dateTime);
        return compact('weeks');

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
        $date = $dt->format('Y-m-d');
        $stopDate = date('Y-m-d', strtotime($date . '+1 day'));

        $sql = Docket::whereBetween('docket_date', [$date, $stopDate])->where('transaction', "SA")->orWhere('transaction', "IV");

        $dockets = $sql->get();
        $stock = Stock::where('custom1', '!=', null)->first();

        $result_array = array($stock->custom1 => 0, $stock->custom2 => 0, 'extra' => 0, 'others' => 0);
        foreach ($dockets as $docket) {
            $docketLines = $docket->docketLines()->with('stock')->get();
            foreach ($docketLines as $dl) {
                if ($dl['stock']['cat1'] != 'TASTE' && $dl['stock']['cat1'] != 'EXTRA' && $dl['size_level'] != 0) {
                    $result_array[$stock['custom' . $dl['size_level']]] += $dl['quantity'];
                } else if ($dl['size_level'] == 0) {
                    $result_array['others'] += $dl['quantity'];
                } else {

                    $result_array['extra'] += $dl['quantity'];
                }
            }
        }
        $dataGroup = array(
            ['size' => $stock->custom1, 'quantity' => $result_array[$stock->custom1]],
            ['size' => $stock->custom2, 'quantity' => $result_array[$stock->custom2]],
            ['size' => 'extra', 'quantity' => $result_array['extra']],
            ['size' => 'others', 'quantity' => $result_array['others']],

        );
        return compact('dataGroup');
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
}
