<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\User;
use Hash;
use Auth;
use App\Traits\ApiTrait;


class AdminController extends Controller
{
    use ApiTrait;

    // create a new client
    public function newClient(Request $request){
        // check if current user is an admin
        if(Auth::user()->isAdmin()){
            $validator = Validator::make($request->all(), [ 
                'app_name' => 'required', 
                'client_id' => 'required', 
                'client_secret' => 'required',
                'confirm_client_secret' => 'required|same:client_secret', 
            ]);
            if ($validator->fails()) { 
                return response()->json(['error'=>$validator->errors()], 401);            
            }
            //check if app exists
            $check = User::where("email", $request->input('client_id'))->first();
            if(!$check){
                $client = new User();
                $client->name = $request->input('app_name');
                $client->email = $request->input('client_id');
                $client->password = Hash::make($request->input('client_secret'));
                $client->user_cat = $this->getUserCatCode("CLIENT");
                $client->save();
    
                return json_encode([
                    "client_created" => true,
                    "client_id" => $client->email,
                    "client_secret" => $request->input('client_secret'),
                ]);
            }else{
                return json_encode([
                    "response" => 419,
                    "message" => "Client app already exists"
                ]);
            }
        }else{
            return json_encode([
                "response" => 401,
                "message" => "Unauthorized"
            ]);
        }
    }
}
