<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Docket extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = "Docket";
    protected $primaryKey = "docket_id";
    public $timestamps = false;

    // protected $hidden = [
    //     "staff_id",
    //     "customer_id",
    //     "transaction",
    //     "custom",
    //     "payment_id",
    //     "original_id",
    //     "origin",
    //     "drawer",
    //     "comments",

    //     "discount",
    //     "rounding",

    //     "commission",
    //     "bonus",
    //     "archive",
    //     "actual_id",

    //     "points_earned",
    //     "member_barcode",
    // ];

    public function docketlines()
    {
        return $this->hasMany("App\DocketLine", "docket_id", "docket_id");
    }
}
