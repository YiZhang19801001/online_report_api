<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistDocketLine extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = "HistDocketLine";
    public $timestamps = false;
}
