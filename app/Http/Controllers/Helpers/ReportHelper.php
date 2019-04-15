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
        return compact('date', 'sales', 'numberOfTransactions');
    }

    public function reportsForLast7Days($date)
    {
        $reports = array();
        return array(compact('reports'));
    }
}
