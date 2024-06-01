<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class YBitNotification extends Notification implements ShouldQueue {
    public $connection;

    public $queue;

    public $delay = 5;

    protected $title;

    protected $body;

    protected $category = "";

    protected $subcategory = "";

    public function __construct() {
        $this->connection = config('queue.default');
        $this->queue = config('queue.connections.redis.queue');
    }

    protected abstract function getType();

    public abstract function toNotification($notifiable);
}