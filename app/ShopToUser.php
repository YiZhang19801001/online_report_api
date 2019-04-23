<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopToUser extends Model
{
    protected $connection = 'mysql';
    protected $table = "shoptouser";
    protected $primaryKey = "id";
    public $timestamps = false;
}
