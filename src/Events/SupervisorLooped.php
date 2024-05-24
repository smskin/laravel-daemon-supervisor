<?php

namespace SMSkin\LaravelSupervisor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SMSkin\LaravelSupervisor\Supervisor;

class SupervisorLooped
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Supervisor $supervisor)
    {
    }
}
