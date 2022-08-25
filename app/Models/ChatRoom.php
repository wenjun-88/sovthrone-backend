<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatRoom extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'title',
        'type',
        'owner_id',
        'owner_type'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = $model->{$model->getKeyName()} ?: (string)Str::orderedUuid();
        });
    }

    public static $publicAttributes = [
        'id',
        'title',
        'type',
    ];

    public function toData()
    {
        return $this->only(static::$publicAttributes);
    }

    /**
     * ChatRoom owner, can be any model (polymorph)
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function owner()
    {
        return $this->morphTo();
    }

    //================================================================================
    // messages
    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class)->latest();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, ChatRoomUser::class,
            'chat_room_id', 'user_id', 'id', 'id')
            ->withPivot(['last_seen']);
    }

    public function target()
    {
        return $this->belongsToMany(User::class, ChatRoomUser::class,
            'chat_room_id', 'user_id', 'id', 'id')
            ->withPivot(['last_seen']);
    }

    /**
     * To eager load the last message of this chatRoom
     * @param $query
     * @return mixed
     */
    public function scopeWithLastMessage($query)
    {
        $query->with(['lastMessage', 'lastMessage.sender:id,name']);
        return $query;
    }


    public function getModelKey($target)
    {
        if (is_numeric($target))
        {
            return $target;
        }
        else if (is_object($target) && is_subclass_of($target, \Illuminate\Database\Eloquent\Model::class))
        {
            return $target->getKey();
        }
        else
        {
            return $target;
        }
    }

    //================================================================================
    // type: pm
    public function scopePrivate($query)
    {
        return $query->where('type', 'pm');
    }

    /**
     * To find the private chatRoom with specific target user
     * @param $query
     * @param $target
     * @return mixed
     */
    public function scopeWherePMTarget($query, $target)
    {
        return $query
            ->whereHas('target', function ($query) use ($target) {
                $query
                    ->where('id', $this->getModelKey($target));
            });
    }

    /**
     * To eager load the target user (the user who not self)
     * @param $query
     * @param $self
     * @return mixed
     */
    public function scopeWithPMTarget($query, $self)
    {
        return $query->with(['target' => function ($query) use ($self) {
            $query
                ->where('id', '<>', $this->getModelKey($self))
                ->select(['id', 'name']);
        }]);
    }
}
