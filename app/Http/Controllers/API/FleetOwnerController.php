<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Company;

class FleetOwnerController extends Controller
{
    // get fleet owner details
    public function details($id){
        $fleet_owner = Company::findorfail($id);
        // get drivers
        $drivers = $fleet_owner->drivers;
        return json_encode([
            "status" => 200,
            "fleet_owner" => $fleet_owner,
        ]);
    }
}
