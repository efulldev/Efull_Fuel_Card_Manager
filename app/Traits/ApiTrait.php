<?php

namespace App\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

use App\Http\Requests;
use App\EfupayToken;

trait ApiTrait
{
    public $base_uri = 'http://openapi.efupay.net';
    public $appId = "2020010200000008";
    public $appSecret = "116bf6ae7fdc8e1cf0a12b4431b5e1fd";

    // returns user category codes
    public function getUserCatCode($cat_name){
        $code = null;
        switch ($cat_name) {
            case 'CLIENT':
                $code = "49971";
                break;
            case 'FLEET':
                $code = "71332";
                break;
            case 'STATION':
                $code = "57471";
                break;
            case 'ATTENDANT':
                $code = "47731";
                break;
            case 'ADMIN':
                $code = "37011";
                break;
            default:
                $code = null;
                break;
        }
        return $code;
    }

    // bindCardToEfuWallet
    public function bindCardToEfuWallet($access_token, $phone_no, $card_no){
        try{
            $client = new Client(['base_uri' => $this->base_uri]);
            $response = $client->post('/gateway/v1/account/efucard/bind', [
                RequestOptions::JSON => [
                    "loginName" => $phone_no,
                    "cardSn" => $card_no
                ],

                'headers' => [
                    'Accept' => 'application/json',
                    'Content-type' => 'application/json',
                    'accessToken' => "{$access_token}"
                ]
            ]);
            return true;
        }
        catch (RequestException $e) {
            if ($e->hasResponse()) {
                // return Psr7\str($e->getResponse());
                return false;
            }
        }
        return false;
    }


















    // get access token
    public function getEfuPayAccessToken($myAppId, $myAppSecret){
        $now =  date("Y/m/d H:i:s", strtotime("now"));
        $token = EfupayToken::orderBy("id", "DESC")->first();
        $value = null;
        // check if any access token exists on the DB
        if($token){
            // check if token is still valid
            if($token->expires_at > $now){
                // return token
                $value = $token->token;
            }else{// else request a new token 
                try{
                    $client = new Client(['base_uri' => $this->base_uri]);
                    $response = $client->request('POST', '/gateway/v1/token', [
                        'form_params' => [
                            "appId" => $myAppId, //"2020010200000008",
                            "appSecret" => $myAppSecret, //"116bf6ae7fdc8e1cf0a12b4431b5e1fd",
                            "sessionLength" => 30 // minutes
                        ]
                    ]);
                    // Check if a header exists.
                    if ($response->hasHeader('accessToken')) {
                        $value = $response->getHeader('accessToken')[0];
                        $_30min = date("Y/m/d H:i:s", strtotime("+25 minutes"));
                        // save new token
                        $newToken = new EfupayToken();
                        $newToken->token = $value;
                        $newToken->expires_at = $_30min;
                        $newToken->save();
                    }
                } 
                catch (RequestException $e) {
                    if ($e->hasResponse()) {
                        // return Psr7\str($e->getResponse());
                        $value = null;
                    }
                }
            }
        }else{// else request new token
            try{
                $client = new Client(['base_uri' => $this->base_uri]);
                $response = $client->request('POST', '/gateway/v1/token', [
                    'form_params' => [
                        "appId" => $myAppId, //"2020010200000008",
                        "appSecret" => $myAppSecret, //"116bf6ae7fdc8e1cf0a12b4431b5e1fd",
                        "sessionLength" => 30 // minutes
                    ]
                ]);
                // Check if a header exists.
                if ($response->hasHeader('accessToken')) {
                    $value = $response->getHeader('accessToken')[0];
                    $_30min = date("Y/m/d H:i:s", strtotime("+25 minutes"));
                    // save new token
                    $newToken = new EfupayToken();
                    $newToken->token = $value;
                    $newToken->expires_at = $_30min;
                    $newToken->save();
                }
            } 
            catch (RequestException $e) {
                if ($e->hasResponse()) {
                    // return Psr7\str($e->getResponse());
                    $value = null;
                }
            }
        }
        return $value;
    }
}