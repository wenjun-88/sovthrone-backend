<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChatMessageCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data = [];
    protected $chatRoom = null;

    /**
     * Create a new event instance.
     *
     * @param ChatMessage $message
     */
    public function __construct(ChatMessage $message)
    {
        $this->chatRoom = $message->chatRoom;
        $message->load('sender:id,name');

        //
        $this->data = [
            'message' => $message,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $chatRoom = $this->chatRoom;
        $users = $chatRoom->users;
        $this->data['chatRoom'] = $chatRoom;

        if ($chatRoom->type == 'pm') {
            Log::info($chatRoom->id);
            $channels = [
                new Channel("chatRoom.{$chatRoom->id}"),
            ];
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'MessageCreatedEvent';
    }

    public function getChatMessage()
    {
        return $this->data['message'];
    }

    public function getChatRoom()
    {
        return $this->chatRoom;
    }
}
