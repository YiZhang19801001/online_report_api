<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = "Tables";
    protected $primaryKey = "table_id";
    public $timestamps = false;

    protected $hidden = [
        "logon_time",
        "table_shape",
        "table_left",
        "table_top",
        "table_width",
        "table_height",
        "show_state",
        "table_fore_color",
        "table_font_size",
        "state_fore_color",
        "state_font_size",
        "customer_name",
        "table_left_rate",
        "table_top_rate",
        "table_width_rate",
        "table_height_rate",
        "computer_user",
        "start_time",
        "ip",
        "kb_id",
    ];

    public function site()
    {
        return $this->hasOne('App\Site', 'site_id', 'site_id');
    }

}
