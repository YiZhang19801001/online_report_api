<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Docket extends Model
{
    protected $table = "Docket";
    protected $primaryKey = "docket_id";
    public $timestamps = false;
}
