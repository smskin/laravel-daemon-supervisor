<?php

namespace SMSkin\LaravelSupervisor\Examples;

use SMSkin\LaravelSupervisor\Commands\WorkerCommand;

class TestWorkerCommand extends WorkerCommand
{
    protected $signature = 'lst:worker';

    protected $description = 'Run laravel test supervisor worker';

    private bool $run = true;

    public function handle()
    {
        while ($this->run) {
            $this->info('Worker tick');
            sleep(1);
        }
    }

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal, false|int $previousExitCode = 0): int|false
    {
        $this->info('Signal received - ' . $signal);
        $this->run = false;
        switch ($signal) {
            case SIGTERM:
            case SIGQUIT:
                $this->info('Worker terminated (hard)');
                break;
            case SIGINT:   // 2  : ctrl+c
                $this->info('Worker terminated (soft)');
                break;
        }
        return false;
    }
}
