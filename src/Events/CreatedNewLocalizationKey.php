<?php

namespace KgBot\LaravelLocalization\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CreatedNewLocalizationKey
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var string  */
    public $group_name;

    /** @var array */
    public $keys;

    /**
     * Create a new event instance.
     *
     * @param string $group_name
     * @param array $keys
     * @return void
     */
    public function __construct($group_name, $keys)
    {
        $this->group_name = $group_name;
        $this->keys = $keys;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
