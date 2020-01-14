<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    public function company(){
        return $this->belongsTo('App\Company');
    }


    public function cards(){
        return $this->hasMany('App\Card', 'holder_id')->orderBy('id', 'DESC');;
    }
}
