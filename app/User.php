<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\ApiTrait;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, ApiTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // for middlewares
    public function isAdmin(){
        if($this->user_cat == $this->getUserCatCode("ADMIN")){
            return true;
        }else{
            return false;
        }
    }

    public function isClient(){
        if($this->user_cat == $this->getUserCatCode("CLIENT")){
            return true;
        }else{
            return false;
        }
    }

    public function isFleetOwner(){
        if($this->user_cat == $this->getUserCatCode("FLEET")){
            return true;
        }else{
            return false;
        }
    }

    public function isStation(){
        if($this->user_cat == $this->getUserCatCode("STATION")){
            return true;
        }else{
            return false;
        }
    }

    public function isAttendant(){
        if($this->user_cat == $this->getUserCatCode("ATTENDANT")){
            return true;
        }else{
            return false;
        }
    }


    public function isMerchant(){
        if($this->user_cat == $this->getUserCatCode("MERCHANT")){
            return true;
        }else{
            return false;
        }
    }
}
