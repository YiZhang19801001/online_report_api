<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Stock';
    protected $primaryKey = 'stock_id';
    public $timestamps = false;
}
