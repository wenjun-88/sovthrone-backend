<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ChatApiController;
use App\Http\Controllers\API\Auth\AuthUserController;

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
Route::post('/login', [AuthUserController::class, 'login']);
Route::post('/logout', [AuthUserController::class, 'logout'])->middleware('auth:api');

Route::group([
    'middleware' => ['api', 'auth:api'],

], function () {
    Route::get('/me', [AuthUserController::class, 'getMe']);

    Route::group(['prefix' => 'chatRoom'], function () {

        Route::get('/participants', [ChatApiController::class, 'apiGetParticipants'])->name('apiGetParticipants');

        Route::get('/private', [ChatApiController::class, 'getPrivateChatRooms']);
        Route::post('/private/target', [ChatApiController::class, 'getPrivateChatRoomWhereUser']);

        Route::group(['prefix' => '{chatRoom}'], function () {
            // getChatRoomDetails
            Route::get('/', [ChatApiController::class, 'getChatRoomDetails']);

            // getChatRoomMessages
            Route::get('/messages', [ChatApiController::class, 'getChatRoomMessages']);
            Route::post('/messages', [ChatApiController::class, 'getChatRoomMessages']);

            // sendMessage
            Route::post('/message', [ChatApiController::class, 'sendMessageToChatRoom'])->name('sendMessageToChatRoom');

        });
    });


});
