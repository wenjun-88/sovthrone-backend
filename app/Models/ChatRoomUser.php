<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoomUser extends Model
{
    protected $fillable = [
        'chat_room_id',
        'user_id',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    static $pivot = [
        'last_seen',
    ];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
