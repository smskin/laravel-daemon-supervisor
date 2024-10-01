<?php

namespace SMSkin\LaravelSupervisor\Examples;

use Illuminate\Support\Collection;
use SMSkin\LaravelSupervisor\Commands\SupervisorsCommand;
use SMSkin\LaravelSupervisor\Contracts\IWorker;

class TestSupervisorCommand extends SupervisorsCommand
{
    protected $signature = 'lst:supervisor';

    protected $description = 'Run laravel test supervisor';

    /**
     * @return Collection<IWorker>
     */
    protected function getWorkers(): Collection
    {
        return collect([
            new TestWorker(),
        ]);
    }
}
