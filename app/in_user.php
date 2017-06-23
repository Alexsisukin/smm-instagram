<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use InstagramAPI\Instagram;

class in_user extends Model
{
    protected $table = 'in_user';

    public function proxy()
    {
        return $this->hasOne('App\proxy', 'proxy_id', 'proxy_id');
    }
}
