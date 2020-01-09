<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User; 
use Illuminate\Support\Facades\Auth; 
use Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use App\Traits\ApiTrait;

class UserController extends Controller {
    use ApiTrait;
    public $successStatus = 200;
    public $base_uri = 'http://openapi.efupay.net';


    /** 
     * login api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function login(){ 
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){ 
            $user = Auth::user(); 
            if($user->isAdmin() || $user->isClient() || $user->isFleetOwner() || $user->isAttendant()){
                $success['token'] =  $user->createToken('MyApp')->accessToken; 
                $success['user'] = $user;
                return response()->json(['success' => $success], $this->successStatus); 
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
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $input = $request->all(); 
        $input['password'] = bcrypt($input['password']); 
        $user = User::create($input); 
        $user->user_cat = $request->input('user_cat');
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
