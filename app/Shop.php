<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $connection = 'mysql';
    protected $table = "shops";
    protected $primaryKey = "shop_id";
    public $timestamps = false;

}
