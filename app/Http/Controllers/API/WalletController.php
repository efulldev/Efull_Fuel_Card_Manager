<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\CardHash;
use App\WalletTransaction;
use App\Traits\ApiTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;
// use App\utils\Crypt\Crypt_RSA;
use Pikirasa\RSA;

class WalletController extends Controller
{
    use ApiTrait;

    // create new transaction
    public function newTransaction(Request $request){
        if(Auth::user()->isClient()){
            $params = json_decode($request->getContent(), true);
            $amount = round((float) $params['amount'], 2);
            $ref_no = $params['ref_no'];
            $response_code = $params['response_code'];
            $process_id = $params['process_id'];
            $device_id = $params['device_id'];
            $merchant_id = $params['merchant_id'];
            $card_hash = $params['card_hash'];
            $source = $params['source'];
            // $sellerId = "1821000000072";

            // check if wallet hash exists
            $hash = CardHash::where('device_id', $device_id)->where('card_hash', $card_hash)->where('is_valid', true)->first();
            if($hash){
                if($hash->card->company){
                    // check if wallet is bound to a company
                    if($hash->card->company->ext_table_id == $merchant_id){
                        // check that the card is active
                        if($hash->card->is_active){
                            $dest_id = null; // a value will be generated from the efuPay API request 
                            $body = null;
                            $sellerId = $hash->card->company->seller_id;
                            // send data to Efupay API
                            $access_token = $this->getEfuPayAccessToken($this->appId, $this->appSecret);
                            if ($access_token != null) {
                                try{
                                    $data = $process_id.$sellerId.(string)$amount;
                                    $signature = $this->sign($data, "RSA2");


                                    // return json_encode([
                                    //     "outTradeNo" => $process_id,
                                    //     "sellerId" => $sellerId,//$hash->card->card_no,
                                    //     "totalAmount" => (string)$amount,
                                    //     "subject" => "Payment using third-party application",
                                    //     "acquirerCode" => "044",
                                    //     "terminalId" => $device_id,
                                    //     "bankTrxNo" => $ref_no,
                                    //     "sign" => $signature
                                    // ]); exit;

                                    $client = new Client(['base_uri' => $this->base_uri]);
                                    $response = $client->post('/gateway/v1/trade/pos/pay', [
                                        RequestOptions::JSON => [
                                            "outTradeNo" => $process_id,
                                            "sellerId" => $sellerId,//$hash->card->card_no,
                                            "totalAmount" => (string)$amount,
                                            "subject" => "Payment using third-party application",
                                            "acquirerCode" => "044",
                                            "terminalId" => $device_id,
                                            "bankTrxNo" => $ref_no,
                                            "sign" => $signature
                                        ],

                                        'headers' => [
                                            'Accept' => 'application/json',
                                            'Content-type' => 'application/json',
                                            'accessToken' => "{$access_token}"
                                        ]
                                    ]);
                                    $body = $response->getBody()->getContents();
                                    $body = json_decode($body, true);
                                }
                                catch (RequestException $e) {
                                    if ($e->hasResponse()) {
                                        return Psr7\str($e->getResponse());
                                        // return json_encode($access_token);
                                    }
                                }
                            }
                            // check that a record has not been created
                            $rec_check = WalletTransaction::where("trans_ref_id", $ref_no)->orderBy("id", "DESC")->first();
                            if(!$rec_check){
                                // create a record of the transaction
                                $trans = new WalletTransaction();
                                $trans->trans_ref_id = $ref_no;
                                $trans->is_credit = true;
                                $trans->wallet_id = $hash->card->card_no;
                                $trans->company_id = $hash->card->company->id;
                                $trans->card_no = $hash->card->card_no;
                                $trans->description = "Payment using third-party application";
                                $trans->initiator_id = $merchant_id;
                                $trans->source = $source;
                                $trans->destination = "EfuPay";
                                if($body != null){
                                    $trans->destination_id = $body['tradeNo'];
                                    $trans->amount = $body['receiptAmount'];

                                }else{
                                    $trans->destination_id = $dest_id;
                                    $trans->amount = $amount;
                                }
                                $trans->save();
                            }

                            return json_encode([
                                "response" => 200,
                                "message" => "Transaction record persisted",
                                "body" => $body,
                            ]);
                        }else{
                            // invalid card
                            return json_encode([
                                "response" => 500,
                                "message" => "invalid card"
                            ]);
                        }
                    }else{
                        // wallet isn't bound to the selected company
                        return json_encode([
                            "response" => 500,
                            "message" => "wallet isn't bound to the selected company"
                        ]);
                    }
                }else{
                    // wallet isn't bound to company
                    return json_encode([
                        "response" => 500,
                        "message" => "wallet isn't bound to any company"
                    ]);
                }
            }else{
                // invalid wallet binding
                return json_encode([
                    "response" => 500,
                    "message" => "invalid wallet binding"
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
