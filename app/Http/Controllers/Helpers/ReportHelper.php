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
        $stopDate = date('Y-m-d', strtotime($date . '+1 day'));

        $sql = Docket::whereBetween('docket_date', [$date, $stopDate])->where('transaction', "SA")->orWhere('transaction', "IV");

        $sales = $sql->sum('total_inc');
        $numberOfTransactions = $sql->count();
        $reportsForPaymentMethod = self::reportsForPaymentMethod($date, $stopDate);

        return compact('date', 'stopDate', 'sales', 'numberOfTransactions', 'reportsForPaymentMethod');
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
}
