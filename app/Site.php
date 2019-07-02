<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'Site';
    protected $primarykey = 'site_id';
    public $timestamps = false;
}
