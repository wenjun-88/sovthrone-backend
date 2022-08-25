<?php

use App\Models\ChatRoom;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
Broadcast::channel('App.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('chatRoom.{id}', function ($user, $id) {
    $chatRoom = ChatRoom::query()->where('id', $id)->first();
    if ($chatRoom) {
        return $chatRoom->users()->where('id', $user->id)->count() > 0;
    }

    return false;
});
