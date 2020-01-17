<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 6/4/18
 * Time: 1:18 AM.
 */

namespace KgBot\LaravelLocalization\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LaravelLocalizationExported
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messages;

    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel(config('laravel-localization.events.channel', 'channel-name'));
    }
}
