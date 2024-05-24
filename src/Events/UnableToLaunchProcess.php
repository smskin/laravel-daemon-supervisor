<?php

namespace SMSkin\LaravelSupervisor\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SMSkin\LaravelSupervisor\WorkerProcess;

class UnableToLaunchProcess
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly WorkerProcess $process)
    {
    }
}
