<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public function drivers(){
        return $this->hasMany('App\Driver', 'company_id')->orderBy('id', 'DESC');;
    }


    public function cards(){
        return $this->hasMany('App\Card', 'company_id')->orderBy('id', 'DESC');;
    }
}
