<?php

namespace App\Model;


use Illuminate\Database\Eloquent\Model;

class Video extends Model
{

    protected $primaryKey = 'video_id';

    protected $table = 'video';

    public $timestamps = false;


}
