<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Card;
use Validator;

class CardController extends Controller
{
    // get card details
    public function details($card_no){
        $card = Card::where("card_no", $card_no)->first();
        // get driver which card is tied to
        $driver = $card->driver;
        // get company which owns the card
        $company = $card->company;
        // get card transactions
        $transactions = $card->transactions;
        if($card){
            return json_encode([
                "card" => $card
            ]);
        }
        return response()->json(['error'=>'Invalid entry', 'message' => 'Card does not exist'], 401);  
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
