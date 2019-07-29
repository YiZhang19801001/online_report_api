<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistDocket extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = "HistDocket";
    public $timestamps = false;
}
