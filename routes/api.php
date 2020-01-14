<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::POST('getToken', 'API\UserController@clientAuth');

Route::POST('login', 'API\UserController@login');

Route::POST('register', 'API\UserController@register');

Route::POST('efuPayAccessCode', 'API\UserController@efuPayAccessCode');

// request for Access token
Route::GET('/redirect', 'API\UserController@getAccessToken');

Route::group(['middleware' => 'auth:api'], function(){
    // get current user's details
    Route::GET('details', 'API\UserController@details');

    /*
        Account Group
    */
    Route::group(['prefix' => 'account'], function(){
        // get efupay user account using phone no.
        Route::GET('/{phone_no}', 'API\UserController@getEfuPayAcc');
        // get all cards bound to an account
        Route::GET('/cards/{phone_no}', 'API\UserController@getEfuPayAccCards');

    });

    /*
        Fleet Owner Group
    */
    Route::group(['prefix' => 'fleetOwner'], function(){
        // get fleet owner's details
        Route::GET('/{id}', 'API\FleetOwnerController@details');
    });

    /*
        Driver Group
    */
    Route::group(['prefix' => 'driver'], function(){
        // get Driver's details
        Route::GET('/{id}', 'API\DriverController@details');
    });

    /*
        Card Group
    */
    Route::group(['prefix' => 'card'], function(){
        // get Card's details
        Route::GET('/{card_no}', 'API\CardController@details');
        
        // get efu wallet details using card sn
        Route::GET('/efupay/{card_no}', 'API\CardController@efuPayCardData');

        // verify card PIN
        Route::POST('/validate', 'API\CardController@PinValidation');
    });

    /*
        Fuel Station Group
    */
    Route::group(['prefix' => 'station'], function(){

        // get filling station's details
        Route::GET('/{id}', 'API\FuelStationController@details');

        // get transaction history
        Route::GET('/history/{id}', 'API\FuelStationController@history');

    });


    /*
        ADMIN MIDDLEWARE
    */
    Route::group(['middleware' => 'admin'], function () {
        /*
            operations Group
        */
        Route::group(['prefix' => 'operations'], function(){
            // create a new client app auth account
            // create a new wallet account
            Route::POST('/newWallet', 'API\AdminController@newWallet');
        });
    });

    /*
        CLIENT MIDDLEWARE
        ** This is used by client applications who have been authenticated
    */
    Route::group(['middleware' => 'client'], function () {
        /*
            transactions Group
        */
        Route::group(['prefix' => 'transact'], function(){
            // get transaction history
            Route::POST('/new', 'API\WalletController@newTransaction');
        });

        /*
            operations Group
        */
        Route::group(['prefix' => 'operations'], function(){
            // bind a wallet using card sn to a client app account
            Route::POST('/bind/wallet', 'API\ClientController@bindWallet');

        });
    });

});