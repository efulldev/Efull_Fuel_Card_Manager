<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Driver;

class DriverController extends Controller
{
    // get driver's details
    public function details($id){
        $driver = Driver::findorfail($id);
        // get company
        $company = $driver->company;
        // get cards belonging to the driver
        $cards = $driver->cards;

        return json_encode([
            "status" => 200,
            "driver" => $driver,
        ]);
    }
}
