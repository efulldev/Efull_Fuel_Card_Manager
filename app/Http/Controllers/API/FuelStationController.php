<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\FillingStation;
use App\Traits\ApiTrait;

class FuelStationController extends Controller
{
    use ApiTrait;
    
    // get fuel station details
    public function details($id){
        $station = FillingStation::findorfail($id);

        return json_encode([
            "status" => 200,
            "station" => $station,
        ]);
    }

    // get transaction history
    public function history($id){
        return json_encode(null);
    }
}
