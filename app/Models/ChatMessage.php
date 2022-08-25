<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Events\ChatMessageCreatedEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatMessage extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'content'
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = $model->{$model->getKeyName()} ?: (string)Str::orderedUuid();
        });

        static::created(function ($message) {
            $message->chatRoom->touch();
            $message->chatRoom->save();
            event(new ChatMessageCreatedEvent($message));
        });
    }

    // ====================================================================================================
    // relationship

    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id', 'id');
    }
}
