<?php
namespace App\Http\Controllers\Helpers;

use App\Docket;

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
        #
        $date = $dateTime->format('Y-m-d');

        $sql = Docket::whereBetween('docket_date', [$date, $dateTime])->where('transaction', "SA")->orWhere('transaction', "IV");

        $sales = $sql->sum('total_inc');
        $numberOfTransactions = $sql->count();
        $reportsForPaymentMethod = self::reportsForPaymentMethod();
        return compact('date', 'sales', 'numberOfTransactions', 'reportsForPaymentMehod');
    }

    public function reportsForPaymentMethod($collection)
    {
        #
        $groups = $collection->groupBy()->get();
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
