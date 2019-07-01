<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopToUser extends Model
{
    protected $connection = 'mysql';
    protected $table = "shoptouser";

    protected $primaryKey = "id";
    protected $fillable = ['user_id', 'shop_id'];
    public $timestamps = false;

}
