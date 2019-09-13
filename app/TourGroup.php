<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TourGroup extends Model
{
    protected $connection = "sqlsrv";
    protected $table = "TourGroup";
    protected $primaryKey = "group_id";

    public $timestamps = false;
}
