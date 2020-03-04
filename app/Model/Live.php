<?php

namespace App\Model;


use Illuminate\Database\Eloquent\Model;

class Live extends Model
{

    protected $primaryKey = 'live_id';

    protected $table = 'live';

    public $timestamps = false;


}
