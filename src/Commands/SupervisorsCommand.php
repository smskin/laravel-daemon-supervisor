<?php

namespace SMSkin\LaravelSupervisor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use SMSkin\LaravelSupervisor\Contracts\IWorker;
use SMSkin\LaravelSupervisor\Supervisor;

abstract class SupervisorsCommand extends Command
{
    public function handle()
    {
        (new Supervisor(
            $this->getWorkers()
        ))->handleOutputUsing(function ($type, $line) {
            $this->info($line);
        })->monitor();
    }

    /**
     * @return Collection<IWorker>
     */
    abstract protected function getWorkers(): Collection;
}
