<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DocketLine extends Model
{
    protected $connection = "sqlsrv";
    protected $table = "DocketLine";

    public $timestamps = false;

    protected $casts = [
        'stock_id' => 'integer',
        'quantity' => 'integer',
    ];

    // public $hidden = [
    //     "docket_id",
    //     "stock_id",
    //     "cost_ex",
    //     "cost_inc",
    //     "sales_tax",
    //     "sell_ex",
    //     "customer_id",
    //     "serial_no",
    //     "package_id",
    // ];

    public function stock()
    {
        return $this->hasOne('App\Stock', 'stock_id', 'stock_id');
    }
}
