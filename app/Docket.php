<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Docket extends Model
{
    // protected $connection = 'sqlsrv';
    protected $table = "Docket";
    protected $primaryKey = "docket_id";
    public $timestamps = false;
}
