<?php

namespace App\Http\Controllers\API;

use App\Models\ChatRoom;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ChatMessageApiController extends Controller
{
    public function responseWithAccessDenied($message = 'You are not allowed to access this.')
    {
        return response()->json([
            'error' => 'access_denied',
            'message' => $message,
        ], 403);
    }

    public function canAccessChatRoom($authUser, ChatRoom $chatRoom)
    {
        // ensure chatRoom is accessible by user
        if ($chatRoom->type === 'pm' && $chatRoom->users()->where('id', $authUser->id)->count() == 0) {
            return false;
        }

        return true;
    }
}
