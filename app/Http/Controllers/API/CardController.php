<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Card;
use Validator;
use App\Traits\ApiTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;


class CardController extends Controller
{
    use ApiTrait;

    // get card details
    public function details($card_no){
        $card = Card::where("card_no", $card_no)->first();
        if($card){
            // get driver which card is tied to
            $driver = $card->driver;
            // get company which owns the card
            $company = $card->company;
            // get card transactions
            $transactions = $card->transactions;
            return json_encode([
                "card" => $card
            ]);
        }
        return response()->json(['error'=>'Invalid entry', 'message' => 'Card does not exist'], 401);  
    }

    // get efupay account details using card sn
    public function efuPayCardData($card_no){
        $access_token = $this->getEfuPayAccessToken($this->appId, $this->appSecret);
        if ($access_token != null) {
            try{
                $client = new Client(['base_uri' => $this->base_uri]);
                $response = $client->request('GET', '/gateway/v1/account/efucard/'.$card_no, [
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
    }

    // verify card PIN
    public function PinValidation(Request $request){
        $validator = Validator::make($request->all(), [ 
            'card_no' => 'required', 
            'card_pin' => 'required',
        ]);
        if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        $card = Card::where("card_no", $request->input("card_no"))->first();
        if($card){
            if($card->card_pin == $request->input("card_pin")){
                return json_encode([
                    "card" => $card,
                    "card_pin_valid" => true,
                ], JSON_NUMERIC_CHECK);
            }
            else{
                return json_encode([
                    "card_pin_valid" => false
                ]);
            }
        }else{
            return response()->json(['error'=> "Card does not exist"], 401);             
        }
    }
}
