<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Card;
use App\Company;
use App\CardHash;
use Hash;
use App\Traits\ApiTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class ClientController extends Controller
{
    use ApiTrait;
    
    public function bindWallet(Request $request){
        $params = json_decode($request->getContent(), true);
        $extern_id = $params['app_extern_id'];
        $wallet_id = $params['wallet_id'];
        $pin_hash = $params['wallet_pin_hash'];
        $terminal_device_id = $params['terminal_device_id'];
        // check if a company with the extern_table exists
        $com_check = Company::where("ext_table_id", $extern_id)->first();
        // check that company exists
        if($com_check){
            // check if wallet is registered as a card on the local database
            $card_check = Card::where("card_no", $wallet_id)->where("card_pin", $pin_hash)->where("company_id", $com_check->id)->first();
            if($card_check){
                // check that the card belongs to the company
                if($card_check->company->ext_table_id == $com_check->ext_table_id){
                    $access_token = $this->getEfuPayAccessToken($this->appId, $this->appSecret);
                    if($com_check){
                        //check if card exists on the efupay API
                        if ($access_token != null) {
                            try{
                                $client = new Client(['base_uri' => $this->base_uri]);
                                $response = $client->request('GET', '/gateway/v1/account/efucard/'.$wallet_id, [
                                    'headers' => [
                                        'Accept' => 'application/json',
                                        'Content-type' => 'application/json',
                                        'accessToken' => "{$access_token}"
                                    ]
                                ]);
                                $body = $response->getBody()->getContents();
                                // wallet account exists on efu pay API
                                // generate bind hash 
                                $dummy = "ABCDEFGH012345678_";
                                $dummy = str_shuffle($dummy);
                                $card_hash = Hash::make($dummy);
                                // store hash
                                $hash = new CardHash();
                                $hash->card_no = $wallet_id;
                                $hash->card_hash = $card_hash;
                                $hash->device_id = $terminal_device_id;
                                $hash->is_valid = true;
                                $hash->save();
                                // return response
                                return json_encode([
                                        "bind_success" => true,
                                        "wallet" => json_decode($body),
                                        "card_hash" => $card_hash,
                                    ]);
                            }catch(RequestException $e) {
                                if ($e->hasResponse()) {
                                    // return Psr7\str($e->getResponse());
                                    return json_encode([
                                        "bind_success" => false,
                                        "code" => 419,
                                        "message" => Psr7\str($e->getResponse())
                                    ]);
                                }
                            }
                        }else{
                            return json_encode([
                                "bind_success" => false,
                                "code" => 419,
                                "message" => "Invalid access token"
                            ]);
                        }
                    }else{
                        return json_encode([
                            "bind_success" => false,
                            "code" => 404,
                            "message" => "Business not registered"
                        ]);
                    }
                }else{
                    return json_encode([
                        "bind_success" => false,
                        "code" => 419,
                        "message" => "Card does not belong to company account"
                    ]);
                }
            }else{
                return json_encode([
                    "bind_success" => false,
                    "code" => 404,
                    "message" => "Card not found"
                ]);
            }
        }
        else{
            return json_encode([
                "bind_success" => false,
                "code" => 404,
                "message" => "Company does not exist"
            ]);
        }
    }
}
