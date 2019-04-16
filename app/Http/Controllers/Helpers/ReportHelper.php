<?php
namespace App\Http\Controllers\Helpers;

use App\Docket;
use App\Payments;

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
        $dt = new \DateTime($dateTime, new \DateTimeZone('Australia/Sydney'));
        $date = $dt->format('Y-m-d');

        $sql = Docket::whereBetween('docket_date', [$date, $dt])->where('transaction', "SA")->orWhere('transaction', "IV");

        $sales = $sql->sum('total_inc');
        $numberOfTransactions = $sql->count();
        $reportsForPaymentMethod = self::reportsForPaymentMethod($date, $dt);
        return compact('date', 'sales', 'numberOfTransactions', 'reportsForPaymentMethod');
    }

    public function reportsForPaymentMethod($start, $end)
    {
        $sql = Payments::whereBetween('docket_date', [$start, $end]);
        $sum = $sql->sum('amount');
        $groups = $sql
            ->selectRaw("sum(amount) as total, paymenttype, (sum(amount)/$sum) as percentage")
            ->groupBy('paymenttype')
            ->get();
        return $groups;
    }

    public function reportsForLast7Days($date)
    {
        $reports = array();

        $record = array('date', 'value');

        $reports = [
            'all' => array('date', 'value'),
            'alipay' => array('date', 'value'),
            'wechat' => array('date', 'value'),
        ];

        return $reports;
    }
}
