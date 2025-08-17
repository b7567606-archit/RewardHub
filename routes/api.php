<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
    
  
Route::controller(ApiController::class)->group(function(){
    Route::any('check' , 'check');
    Route::any('user-register' , 'one');
    Route::any('check' , 'check');
    Route::any('user-login' , 'two');
    Route::any('update-user-profile' , 'three');
    Route::any('get-profile' , 'four');
    Route::any('change-password' , 'six');
    Route::any('logout-user' , 'seven');

    #post the survey data
    Route::any('post-survey-data' , 'eight');
    Route::any('get-survey-data' , 'nine');
    Route::any('user-send-survey-data' , 'ten');
    Route::any('see-user-earnings' , 'eleven');
    Route::any('spin-details', 'twelve');
    Route::post('post-spin', 'thirteen');

});