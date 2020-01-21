<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User; 
use Illuminate\Support\Facades\Auth; 
use Validator;
use App\Traits\ApiTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class UserController extends Controller {
    use ApiTrait;

    public $successStatus = 200;

    /** 
     * login api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function login(Request $request){ 
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){ 
            $user = Auth::user(); 
            if($user->isAdmin() || $user->isClient() || $user->isFleetOwner() || $user->isAttendant() || $user->isMerchant()){
                if($user->user_cat == $this->getUserCatCode(strtoupper($request->input('acc_type')))){
                    $success['token'] =  $user->createToken('MyApp')->accessToken; 
                    $success['user'] = $user;
                    return response()->json(['success' => $success], $this->successStatus); 
                }else{
                    // revoke tokens
                    foreach ($user->tokens as $key => $token) {
                        $token->revoke();
                    }
                    return response()->json(['error'=>'Unauthorised', 'message' => 'Account type is not defined correctly'], 401); 
                }
            }else{
                // revoke tokens
                foreach ($user->tokens as $key => $token) {
                    $token->revoke();
                }
                return response()->json(['error'=>'Unauthorised', 'message' => 'Account is not permitted'], 401);  
            }
        } 
        else{ 
            return response()->json(['error'=>'Unauthorised', 'message' => 'Invalid login credentials'], 401); 
        } 
    }

    /** 
     * Register api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function register(Request $request){ 
        $validator = Validator::make($request->all(), [ 
            'name' => 'required', 
            'email' => 'required|email', 
            'password' => 'required', 
            'c_password' => 'required|same:password', 
            'acc_type' => 'required',
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $input = $request->all(); 
        $input['password'] = bcrypt($input['password']); 
        $user = User::create($input); 
        $user->user_cat = $this->getUserCatCode(strtoupper($request->input('acc_type')));//$request->input('user_cat');
        $user->save();
        $success['token'] =  $user->createToken('MyApp')->accessToken; 
        $success['user'] =  $user;
        return response()->json(['success'=>$success], $this->successStatus); 
    }

    /** 
     * client auth api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function clientAuth(Request $request){ 
        if(Auth::attempt(['email' => request('client_id'), 'password' => request('client_secret')])){ 
            $client = Auth::user(); 
            if($client->isClient() ){
                $token =  $client->createToken('MyApp')->accessToken; 
                return response()->json(['token' => $token], $this->successStatus); 
            }else{
                // revoke tokens
                foreach ($client->tokens as $key => $token) {
                    $token->revoke();
                }
                return response()->json(['error'=>'Unauthorised', 'message' => 'Account is not permitted'], 401);  
            }
        } 
        else{ 
            return response()->json(['error'=>'Unauthorised', 'message' => 'Client auth failed'], 401); 
        } 
    }

    /** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function details(){ 
        $user = Auth::user(); 
        return response()->json(['user' => $user], $this->successStatus); 
    } 


    // getEfuPayAcc
    public function getEfuPayAcc($phone_no){
        // get access token from Efu Pay
        $access_token = $this->getEfuPayAccessToken($this->appId, $this->appSecret);
        // return json_encode($access_token); exit;
        if ($access_token != null) {
            try{
                $client = new Client(['base_uri' => $this->base_uri]);
                $response = $client->request('GET', '/gateway/v1/account/'.$phone_no, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-type' => 'application/json',
                        'accessToken' => "{$access_token}"
                    ]
                ]);
                    return $response->getBody()->getContents();
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

    // getEfuPayAccCards
    public function getEfuPayAccCards($phone_no){
        // get access token from Efu Pay
        $access_token = $this->getEfuPayAccessToken($this->appId, $this->appSecret);
        if ($access_token != null) {
            try{
                $client = new Client(['base_uri' => $this->base_uri]);
                $response = $client->request('GET', '/gateway/v1/account/efucard/list/'.$phone_no, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-type' => 'application/json',
                        'accessToken' => "{$access_token}"
                    ]
                ]);
                    return $response->getBody()->getContents();
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

     // make API call to external endpoint
     public function efuPayAccessCode(Request $request){
        try{
            $client = new Client(['base_uri' => $this->base_uri]);
            $response = $client->request('POST', '/gateway/v1/token', [
                'form_params' => [
                    "appId" => $request->input('appId'), //"2020010200000008",
                    "appSecret" => $request->input('appSecret'), //"116bf6ae7fdc8e1cf0a12b4431b5e1fd",
                    "sessionLength" => 30
                ]
            ]);
            // Check if a header exists.
            if ($response->hasHeader('accessToken')) {
                return json_encode(
                    [
                        "code" => $response->getStatusCode(),
                        "accessToken" => $response->getHeader('accessToken')[0]
                    ]);
            }
            return null;
        } 
        catch (RequestException $e) {
            if ($e->hasResponse()) {
                return Psr7\str($e->getResponse());
            }
        }
    }

}
