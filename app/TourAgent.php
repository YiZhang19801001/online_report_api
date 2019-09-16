<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TourAgent extends Model
{
    protected $connection = "sqlsrv";
    protected $table = "TourAgent";
    protected $primaryKey = "agent_id";

    public $timestamps = false;
}
