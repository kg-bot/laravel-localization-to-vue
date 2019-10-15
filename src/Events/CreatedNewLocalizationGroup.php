<?php

namespace KgBot\LaravelLocalization\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CreatedNewLocalizationGroup
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var string  */
    public $group_name;

    /**
     * Create a new event instance.
     *
     * @param string $group_name
     * @return void
     */
    public function __construct($group_name)
    {
        $this->group_name = $group_name;
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
