<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CardHash extends Model
{
    //
    public function card(){
        return $this->belongsTo('App\Card', 'card_no', 'card_no');
    }
}
