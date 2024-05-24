<?php

namespace SMSkin\LaravelSupervisor;

use Carbon\CarbonImmutable;
use Closure;
use SMSkin\LaravelSupervisor\Events\UnableToLaunchProcess;
use SMSkin\LaravelSupervisor\Events\WorkerProcessRestarting;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

class WorkerProcess
{
    /**
     * The output handler callback.
     */
    public Closure $output;

    /**
     * The time at which the cool down period will be over.
     */
    public CarbonImmutable $restartAgainAt;

    /**
     * Create a new worker process instance.
     */
    public function __construct(public readonly Process $process)
    {
    }

    /**
     * Start the process.
     */
    public function start(Closure $callback): static
    {
        $this->output = $callback;

        $this->coolDown();

        $this->process->start($callback);

        return $this;
    }

    /**
     * Pause the worker process.
     *
     * @return void
     * @throws ExceptionInterface
     */
    public function pause(): void
    {
        $this->sendSignal(SIGUSR2);
    }

    /**
     * Instruct the worker process to continue working.
     *
     * @return void
     * @throws ExceptionInterface
     */
    public function continue(): void
    {
        $this->sendSignal(SIGCONT);
    }

    /**
     * Evaluate the current state of the process.
     *
     * @return void
     */
    public function monitor(): void
    {
        if ($this->process->isRunning() || $this->coolingDown()) {
            return;
        }

        $this->restart();
    }

    /**
     * Restart the process.
     *
     * @return void
     */
    public function restart(): void
    {
        if ($this->process->isStarted()) {
            event(new WorkerProcessRestarting($this));
        }

        $this->start($this->output);
    }

    /**
     * Terminate the underlying process.
     *
     * @return void
     * @throws ExceptionInterface
     */
    public function terminate(): void
    {
        $this->sendSignal(SIGTERM);
    }

    /**
     * Stop the underlying process.
     *
     * @return void
     */
    public function stop(): void
    {
        if ($this->process->isRunning()) {
            $this->process->stop();
        }
    }

    /**
     * Send a POSIX signal to the process.
     *
     * @throws ExceptionInterface
     */
    protected function sendSignal(int $signal): void
    {
        try {
            $this->process->signal($signal);
        } catch (ExceptionInterface $e) {
            if ($this->process->isRunning()) {
                throw $e;
            }
        }
    }

    /**
     * Begin the cool-down period for the process.
     *
     * @return void
     */
    protected function coolDown(): void
    {
        if ($this->coolingDown()) {
            return;
        }

        $this->restartAgainAt = !$this->process->isRunning()
            ? CarbonImmutable::now()->addMinute()
            : null;

        if (!$this->process->isRunning()) {
            event(new UnableToLaunchProcess($this));
        }
    }

    /**
     * Determine if the process is cooling down from a failed restart.
     *
     * @return bool
     */
    public function coolingDown(): bool
    {
        return isset($this->restartAgainAt) &&
            CarbonImmutable::now()->lt($this->restartAgainAt);
    }

    /**
     * Set the output handler.
     */
    public function handleOutputUsing(Closure $callback): static
    {
        $this->output = $callback;

        return $this;
    }

    /**
     * Pass on method calls to the underlying process.
     */
    public function __call(string $method, array $parameters)
    {
        return $this->process->{$method}(...$parameters);
    }
}
