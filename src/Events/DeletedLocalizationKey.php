<?php

namespace KgBot\LaravelLocalization\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class DeletedLocalizationKey
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var string */
    public $locale;

    /**
     * Create a new event instance.
     *
     * @param string $locale
     * @return void
     */
    public function __construct($locale)
    {
        $this->locale = $locale;
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
