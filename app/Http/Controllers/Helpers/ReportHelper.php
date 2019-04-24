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
        $dt = new \DateTime($dateTime, new \DateTimeZone('Australia/Sydney'));
        $date = $dt->format('Y-m-d');

        $sql = Docket::whereBetween('docket_date', [$date, $dt])->where('transaction', "SA")->orWhere('transaction', "IV");

        $sales = $sql->sum('total_inc');
        $numberOfTransactions = $sql->count();
        $reportsForPaymentMethod = self::reportsForPaymentMethod($date, $dt);
        $dockets = $sql->get();
        $stock = Stock::where('custom1', '!=', null)->first();

        $dataGroup = array($stock->custom1 => 0, $stock->custom2 => 0, 'extra' => 0, 'others' => 0);
        foreach ($dockets as $docket) {
            $docketLines = $docket->docketLines()->with('stock')->get();
            foreach ($docketLines as $dl) {
                if ($dl['stock']['cat1'] != 'TASTE' && $dl['stock']['cat1'] != 'EXTRA' && $dl['size_level'] != 0) {
                    $dataGroup[$stock['custom' . $dl['size_level']]] += $dl['quantity'];
                } else if ($dl['size_level'] == 0) {
                    $dataGroup['others'] += $dl['quantity'];
                } else {

                    $dataGroup['extra'] += $dl['quantity'];
                }
            }
        }
        return compact('date', 'sales', 'numberOfTransactions', 'reportsForPaymentMethod', 'dataGroup');
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

}
