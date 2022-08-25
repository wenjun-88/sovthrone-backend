<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;


class ChatApiController extends Controller
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
        // TODO: use role permission system to check

        // ensure chatRoom is accessible by user
        if ($chatRoom->type === 'pm' && $chatRoom->users()->where('id', $authUser->id)->count() == 0) {
            return false;
        }

        // TODO: for lobby chatRoom, check if user can enter lobby

        return true;
    }

    public function sendMessageToChatRoom(Request $request, ChatRoom $chatRoom)
    {
        $authUser = $request->user();

        // ensure chatRoom is accessible by user
        if (!$this->canAccessChatRoom($authUser, $chatRoom)) {
            return $this->responseWithAccessDenied();
        }

        $messageBody = $request->get('message');

        $data = [
            'user_id' => $authUser->id,
            'content' => $messageBody
        ];

        $message = $chatRoom->messages()->create($data);

        return response()->json($message);
    }

    // get a list of message rooms
    public function getPrivateChatRooms(Request $request)
    {
        $authUser = $request->user();
        $body = $request->all();

        // paging
        $page = (int)Arr::get($body, 'page', 1);
        $limit = (int)Arr::get($body, 'limit', 50);
        $offset = ($page - 1) * $limit;

        $query = $authUser->privateChatRooms()
            ->whereHas('messages');
        $countQuery = clone $query;

        // search
        $search = Arr::get($body, 'search');
        $q = Arr::get($search, 'value');
        if ($q) {
            $keyword = "%$q%";
            $query = $query->where(function ($query) use ($keyword) {
                $query = $query->whereHas('users', function ($query) use ($keyword) {
                    $query = $query->where('name', 'LIKE', $keyword);
                });
            });
        }

        // sort by updated_at
        $query = $query->orderBy('chat_rooms.updated_at', 'desc');

        $recordsFiltered = (clone $query)->count();
        $records = $query->offset($offset)->limit($limit)->get(ChatRoom::$publicAttributes);
        if ($q !== null) {
            $recordsTotal = $countQuery->count();
        } else {
            $recordsTotal = $recordsFiltered;
        }

        return response()->json([
            'draw' => Arr::get($body, 'draw', 0),
            'data' => $records,
            'recordsFiltered' => $recordsFiltered,
            'recordsTotal' => $recordsTotal,
        ]);
    }

    public function getPrivateChatRoomWhereUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required_without:user_uuid|uuid',
            'user_uuid' => 'required_without:user_id|uuid',
        ]);

        $user = $request->user();
        $target_user_id = $request->get('user_id', $request->get('user_uuid'));
        $targetUser = User::query()->where('id', $target_user_id)->first();

        $query = $user->privateChatRoomWhereTarget($targetUser);
        $chatRoom = $query->first(ChatRoom::$publicAttributes);

        if (is_null($chatRoom)) {
            $chatRoom = $user->createPrivateChatRoom();
            $chatRoom->users()->sync([
                $user->id, $targetUser->id,
            ]);

            $chatRoom = $query->first(ChatRoom::$publicAttributes);
        }

        return response()->json($chatRoom);
    }

    public function getChatRoomDetails(Request $request, ChatRoom $chatRoom)
    {
        $authUser = $request->user();

        // ensure chatRoom is accessible by user
        if (!$this->canAccessChatRoom($authUser, $chatRoom)) {
            return $this->responseWithAccessDenied();
        }

        $chatRoom->load(['target' => function ($query) use ($authUser) {
            $query
                ->where('id', '<>', $authUser->id)
                ->select(['id', 'name']);
        }]);

        $chatRoom->load(['lastMessage', 'lastMessage.sender:id,name']);

        return response()->json($chatRoom);
    }

    public function getChatRoomMessages(Request $request, ChatRoom $chatRoom)
    {
        $authUser = $request->user();

        // ensure chatRoom is accessible by user
        if (!$this->canAccessChatRoom($authUser, $chatRoom)) {
            return $this->responseWithAccessDenied();
        }

        $body = $request->all();

        // paging
        $page = (int)Arr::get($body, 'page', 1);
        $limit = (int)Arr::get($body, 'limit', 5000);
        $offset = ($page - 1) * $limit;

        $getMessages = function ($chatRoom, $offset, $limit) use ($authUser) {
            $query = $chatRoom
                ->messages()
                ->with(['sender:id,name']);

            // messages suppose to be pull from latest first (last-in-first-out)
            $query->orderBy('created_at', 'asc');

            $messages = $query
                ->offset($offset)
                ->limit($limit)
                ->get();

            return $messages;
        };
        $messages = $getMessages($chatRoom, $offset, $limit);
        $recordsTotal = $chatRoom
            ->messages()
            ->count();

        return response()->json([
            'draw' => Arr::get($body, 'draw', 0),
            'data' => $messages,
//            'recordsFiltered' => $recordsFiltered,
            'recordsTotal' => $recordsTotal,
        ]);
    }

    public function apiGetParticipants(Request $request)
    {
        $authUser = $request->user();

        $body = $request->all();

        // paging
        $limit = Arr::get($body, 'length', function () use ($body) {
            return Arr::get($body, 'limit', 50);
        });
        $offset = Arr::get($body, 'start', function () use ($body, $limit) {
            $page = Arr::get($body, 'page', 1);
            return ($page - 1) * $limit;
        });

        $query = User::query();

        $filterRoles = function () {};

        $query = $query->whereHas('roles', function ($query) use ($filterRoles) {
            $query->orWhere('name', 'User');
            $filterRoles($query);
        });

        $countQuery = clone $query;

        // search
        $search = Arr::get($body, 'search');
        if (is_array($search)) {
            $q = Arr::get($search, 'value');
        } else {
            $q = $search;
        }
        if ($q) {
            $keyword = "%$q%";
            $query = $query->where(function ($query) use ($keyword) {
                $query = $query->where('name', 'LIKE', $keyword);
            });
        }

        $recordsFiltered = (clone $query)->count();
        $records = $query->offset($offset)->limit($limit)->get([
            'id',
            'name',
        ]);

        if ($q !== null) {
            $recordsTotal = $countQuery->count();
        } else {
            $recordsTotal = $recordsFiltered;
        }

        return response()->json([
            'draw' => Arr::get($body, 'draw', 0),
            'data' => $records,
            'recordsFiltered' => $recordsFiltered,
            'recordsTotal' => $recordsTotal,
        ]);
    }
}
