<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $connection = 'mysql';
    protected $table = "shops";
    protected $primaryKey = "shop_id";

    protected $fillable = ['shop_name', 'database_ip', 'username', 'password', 'database_name', 'port'];

    public $timestamps = false;

}
