<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistPayments extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = "HistPayments";
    public $timestamps = false;
}
