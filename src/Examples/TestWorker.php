<?php

namespace SMSkin\LaravelSupervisor\Examples;

use SMSkin\LaravelSupervisor\Contracts\IWorker;

class TestWorker implements IWorker
{
    public function getArtisanCommand(): string
    {
        return 'lst:worker';
    }
}
