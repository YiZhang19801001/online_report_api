<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $connection = "sqlsrv";
    protected $table = "Customer";
    protected $primaryKey = "customer_id";

    public $timestamps = false;
}
