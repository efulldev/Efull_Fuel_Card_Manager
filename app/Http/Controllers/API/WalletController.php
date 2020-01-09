<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use WalletTransaction;
use App\Traits\ApiTrait;

class WalletController extends Controller
{
    use ApiTrait;

    // create new transaction
    public function newTransaction(Request $request){
        if(Auth::user()->isClient()){
            return json_encode([
                "response" => 200,
                "user" => Auth::user()->user_cat,
            ]);
        }else{
            return json_encode([
                "response" => 401,
                "message" => "Unauthorized"
            ]);
        }
    }

}
