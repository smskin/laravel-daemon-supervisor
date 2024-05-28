<?php

namespace SMSkin\LaravelSupervisor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use SMSkin\LaravelSupervisor\Contracts\IWorker;
use SMSkin\LaravelSupervisor\Supervisor;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Process\Exception\ExceptionInterface;

abstract class SupervisorsCommand extends Command implements SignalableCommandInterface
{
    private Supervisor $supervisor;

    public function handle()
    {
        $this->start();
    }

    protected function start(): void
    {
        $this->supervisor = (new Supervisor(
            $this->getWorkers()
        ))->handleOutputUsing(function ($type, $line) {
            $this->output->write($line);
        });

        $this->supervisor->monitor();
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM, SIGUSR2, SIGCONT];
    }

    /**
     * @throws ExceptionInterface
     */
    public function handleSignal(int $signal, false|int $previousExitCode = 0): int|false
    {
        $this->info('Supervisor: Signal received - ' . $signal);
        switch ($signal) {
            case SIGTERM:
            case SIGQUIT:
                $this->supervisor->terminate($signal);
                $this->info('Supervisor terminated (hard)');
                break;
            case SIGINT:   // 2  : ctrl+c
                $this->supervisor->terminate($signal);
                $this->info('Supervisor terminated (soft)');
                break;
            case SIGUSR2:
                $this->supervisor->pause();
                $this->info('Supervisor paused');
                break;
            case SIGCONT:
                $this->supervisor->continue();
                $this->info('Supervisor paused');
                break;
        }
        return false;
    }

    /**
     * @return Collection<IWorker>
     */
    abstract protected function getWorkers(): Collection;
}
