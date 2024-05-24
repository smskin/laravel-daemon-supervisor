<?php

namespace SMSkin\LaravelSupervisor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use SMSkin\LaravelSupervisor\Contracts\IWorker;
use SMSkin\LaravelSupervisor\Supervisor;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Process\Exception\ExceptionInterface;

abstract class DaemonCommand extends Command implements SignalableCommandInterface
{
    private Supervisor $supervisor;

    public function handle(): int
    {
        $this->supervisor = $supervisor = (new Supervisor($this->getWorkers()))
            ->handleOutputUsing(function ($type, $line) {
                $this->info($line);
            });
        $supervisor->start();
        return -1;
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    /**
     * @throws ExceptionInterface
     */
    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        if ($signal === SIGINT) {
            $this->supervisor->terminate($signal);
        }
        return $signal;
    }

    /**
     * @return Collection<IWorker>
     */
    abstract protected function getWorkers(): Collection;
}
