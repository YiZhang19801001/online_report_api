<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    protected $connection = "sqlsrv";
    protected $table = "payments";

    public $timestamps = false;
}
