<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    public function company(){
        return $this->belongsTo('App\Company');
    }

    public function driver(){
        return $this->belongsTo('App\Driver');
    }

    public function transactions(){
        return $this->hasMany('App\WalletTransaction', 'card_no')->orderBy('id', 'DESC');;
    }
}
