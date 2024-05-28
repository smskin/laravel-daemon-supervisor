<?php

namespace SMSkin\LaravelSupervisor;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use SMSkin\LaravelSupervisor\Contracts\IWorker;
use SMSkin\LaravelSupervisor\Events\SupervisorLooped;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;
use Throwable;

class Supervisor
{
    /**
     * @var Collection<WorkerProcess>
     */
    private Collection $processes;
    private Closure $output;
    private bool $working = true;

    /**
     * @param Collection<IWorker> $workers
     */
    public function __construct(private readonly Collection $workers)
    {
        $this->processes = $this->createProcesses();
        $this->output = static function () {
        };
    }

    public function monitor()
    {
        while (true) {
            sleep(1);
            $this->loop();
        }
    }

    /**
     * Pause all the worker processes.
     *
     * @return void
     * @throws ExceptionInterface
     */
    public function pause(): void
    {
        $this->working = false;

        $this->processes->each(static function (WorkerProcess $process) {
            $process->pause();
        });
    }

    /**
     * Instruct all the worker processes to continue working.
     *
     * @return void
     * @throws ExceptionInterface
     */
    public function continue(): void
    {
        $this->working = true;
        $this->processes->each(static function (WorkerProcess $process) {
            $process->continue();
        });
    }

    /**
     * Terminate this supervisor process and all of its workers.
     * @throws ExceptionInterface
     */
    public function terminate(int|null $status = null): void
    {
        $status ??= 0;
        $this->working = false;
        $this->processes->each(static function (WorkerProcess $process) use ($status) {
            $process->terminate($status);
        });

        while ($this->processes->filter(static function (WorkerProcess $process) {
            return $process->process->isRunning();
        })->collapse()->count()) {
            sleep(1);
        }
        sleep(1);

        exit($status);
    }

    /**
     * Set the output handler.
     */
    public function handleOutputUsing(Closure $callback): static
    {
        $this->output = $callback;

        return $this;
    }

    private function loop(): void
    {
        try {
            if ($this->working) {
                $this->processes->each(static function (WorkerProcess $process) {
                    $process->monitor();
                });
            }
            event(new SupervisorLooped($this));
        } catch (Throwable $e) {
            app(ExceptionHandler::class)->report($e);
        }
    }

    /**
     * @return Collection<WorkerProcess>
     */
    private function createProcesses(): Collection
    {
        return $this->workers->map(function (IWorker $worker) {
            return $this->createProcess($worker)
                ->handleOutputUsing(function ($type, $line) {
                    call_user_func($this->output, $type, $line);
                });
        });
    }

    private function createProcess(IWorker $worker): WorkerProcess
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';
        $command = 'exec ' . $escape . PHP_BINARY . $escape . ' artisan ' . $worker->getArtisanCommand();

        return new WorkerProcess(
            Process::fromShellCommandline($command)
                ->setTimeout(null)
                ->disableOutput()
        );
    }
}
