<?php

namespace App\Traits;

trait ApiTrait
{
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
}