<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\User;
use App\Company;
use App\Card;
use Hash;
use Auth;
use App\Traits\ApiTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;
use App\Http\Requests;


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

    // newWallet
    public function newWallet(Request $request){
        // get access token from Efu Pay
        $access_token = $this->getEfuPayAccessToken($this->appId, $this->appSecret);
        $card_sn = $this->genWalletId($request->input('phone_no'));
        if ($access_token != null) {
            try{
                $client = new Client(['base_uri' => $this->base_uri]);
                $response = $client->post('/gateway/v1/account/open', [
                    RequestOptions::JSON => [
                        "customerType" => "2",
                        "countryCode" => "234",
                        "loginName" => $request->input('phone_no'),
                        "phoneNumber" => $request->input('phone_no'),
                        "firstName" => $request->input('first_name'),
                        "lastName" => $request->input('last_name'),
                        "companyName" => $request->input('company_name'),
                        "loginPassword" => $request->input('login_password'),
                        "pin" => $request->input('card_pin'),
                        "cardSn" => $card_sn
                    ],

                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-type' => 'application/json',
                        'accessToken' => "{$access_token}"
                    ]
                ]);
                $body = $response->getBody();
                // check if account creation was successful
                if(strlen($body) == 0){ //successfully created on the efull pay platform
                    // create user account on fuel card database
                    // this user account would be used by the company manager to
                    // monitor thier dashboard
                    $user = new User();
                    $user->name = $request->input('first_name')." ".$request->input('last_name');
                    $user->password = bcrypt($request->input('login_password')); 
                    $user->user_cat = $this->getUserCatCode(strtoupper($request->input('acc_type')));
                    $user->phone_no = $request->input('phone_no');
                    $user->email = $request->input('email');
                    $user->save();
                    if($user->user_cat != $this->getUserCatCode("ADMIN")){
                        // create a company record on the db
                        $com = new Company();
                        $com->company_name = $request->input("company_name");
                        $com->wallet_id = $card_sn;
                        $com->company_address = $request->input("company_address");
                        $com->company_phone = $request->input("phone_no");
                        $com->company_email = $request->input("email");
                        $com->reg_number = $request->input("reg_number");
                        $com->is_active = true;
                        $com->save();
                        // create company default card
                        $card = new Card();
                        $card->card_no = $card_sn;
                        $card->card_pin = hash('sha256', "123456");
                        $card->expiry_month = "12";
                        $card->expiry_year = "25";
                        $card->company_id = $com->id;
                        $card->holder_id = null;
                        $card->is_active = true;
                        $card->save();
                        // bind card_sn to company wallet on efuPay
                        $card_binding = $this->bindCardToEfuWallet($access_token, $com->company_phone, $com->wallet_id);
                        // notify admin of success
                        return json_encode([
                            "code" => $response->getStatusCode(),
                            "account" => $user,
                            "company" => $com,
                            "wallet_id_binding"=> $card_binding,
                        ]);
                    }else{
                        // notify admin of success
                        return json_encode([
                            "code" => $response->getStatusCode(),
                            "account" => $user,
                        ]);
                    }
                }
                return json_encode([
                    "code" => $response->getStatusCode(),
                    "body" => $body,
                ]);
            }
            catch (RequestException $e) {
                if ($e->hasResponse()) {
                    return Psr7\str($e->getResponse());
                    // return json_encode($access_token);
                }
            }
        }
        return null;
    }

}
