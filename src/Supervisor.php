<?php

namespace SMSkin\LaravelSupervisor;

use Closure;
use Illuminate\Support\Collection;
use SMSkin\LaravelSupervisor\Contracts\IWorker;
use SMSkin\LaravelSupervisor\Events\SupervisorLooped;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

class Supervisor
{
    private Closure $output;

    /**
     * @var Collection<WorkerProcess>
     */
    private Collection $processes;

    public bool $working = false;

    /**
     * @param Collection<IWorker> $workers
     * @param int|null $nice
     */
    public function __construct(private readonly Collection $workers, int|null $nice = null)
    {
        if ($nice) {
            proc_nice($nice);
        }

        $this->createProcesses();
        $this->output = static function () {
        };
    }

    public function start(): void
    {
        $this->working = true;
        $this->monitor();
    }

    /**
     * @throws ExceptionInterface
     */
    public function terminate(int|null $status = null): void
    {
        $status ??= 0;
        $this->working = false;
        $this->processes->each(static function (WorkerProcess $process) {
            $process->terminate();
        });

        while ($this->processes->filter(static function (WorkerProcess $process) {
            return $process->process->isRunning();
        })->collapse()->count()) {
            sleep(1);
        }
        exit($status);
    }

    public function monitor(): void
    {
        while ($this->working) {
            sleep(1);
            $this->loop();
        }
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
        $this->processes->each(static function (WorkerProcess $process) {
            $process->monitor();
        });
        event(new SupervisorLooped($this));
    }

    private function createProcesses(): void
    {
        $this->processes = collect();
        $this->workers->each(function (IWorker $worker) {
            $this->processes->push($this->createProcess($worker)->handleOutputUsing(
                function ($type, $line) use ($worker) {
                    echo "\n--------\n";
                    echo $worker->getSignature() . PHP_EOL;
                    echo date('Y-m-d H:i:s') . PHP_EOL;
                    echo 'Message: ' . PHP_EOL;
                    call_user_func($this->output, $type, $line);
                }
            ));
        });
    }

    private function createProcess(IWorker $worker): WorkerProcess
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        return new WorkerProcess(Process::fromShellCommandline(
            'exec ' . $escape . PHP_BINARY . $escape . ' artisan ' . $worker->getArtisanCommand()
        )->setTimeout(null)->disableOutput());
    }
}
