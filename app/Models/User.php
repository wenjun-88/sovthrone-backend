<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = $model->{$model->getKeyName()} ?: (string)Str::orderedUuid();
        });
    }

    public function ownChatRooms()
    {
        return $this->morphMany(ChatRoom::class, 'owner');
    }

    public function createChatRoom(array $data = [], $type = '')
    {
        $data = array_merge($data, [
            'type' => $type
        ]);
        return $this->ownChatRooms()->create($data);
    }

    public function createPrivateChatRoom(array $data = [])
    {
        return $this->createChatRoom($data, 'pm', true);
    }

    public function chatRooms()
    {
        return $this->belongsToMany(ChatRoom::class, ChatRoomUser::class, 'user_id', 'chat_room_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'user_id');
    }

    public function privateChatRooms()
    {
        return $this->chatRooms()
            ->private()
            ->withPMTarget($this)
            ->with(['users'])
            ->withLastMessage();
    }

    public function privateChatRoomWhereTarget($target)
    {
        return $this->privateChatRooms()
            ->wherePMTarget($target);
    }
}
