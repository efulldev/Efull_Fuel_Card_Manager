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


class UserController extends Controller {
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
            $success['token'] =  $user->createToken('MyApp')-> accessToken; 
            return response()->json(['success' => $success], $this->successStatus); 
        } 
        else{ 
            return response()->json(['error'=>'Unauthorised'], 401); 
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
        $success['token'] =  $user->createToken('MyApp')-> accessToken; 
        $success['name'] =  $user->name;
        return response()->json(['success'=>$success], $this->successStatus); 
    }
/** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function details(){ 
        $user = Auth::user(); 
        return response()->json(['success' => $user], $this->successStatus); 
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


    private function curlRequest($url, $method, $data_string){
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "utf-8",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data_string,
            CURLOPT_HTTPHEADER => array(
                // Set here requred headers
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "Content-Type:  form-data",
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }
}
