<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PosHeadShop extends Model
{
    protected $connection = "sqlsrv";
    protected $table = "Shop";

    public $timestamps = false;
}
