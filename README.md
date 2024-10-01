# Basic Library for Implementing Child Processes in Laravel

The idea for this library originated from studying the source code of [Laravel Horizon](https://laravel.com/docs/11.x/horizon).

Horizon starts a master process that spawns child processes to execute tasks from queues.

## How It Works

We start a master process (`supervisor`), which spawns and manages child processes (`worker`).

The master process is subscribed to [PCNTL](https://www.php.net/manual/ru/intro.pcntl.php) signals. Upon receiving a signal, it first terminates the child processes and then stops itself.

## Usage

### Master Process (supervisor) - CLI
The artisan command class for the supervisor should extend `SMSkin\LaravelSupervisor\Commands\SupervisorsCommand`.

In the class, you need to implement the method `protected function getWorkers(): Collection`, which returns a collection of worker process models (classes that implement the `SMSkin\LaravelSupervisor\Contracts\IWorker` interface).

You can find an example of an artisan command in `./src/Examples/TestSupervisorCommand.php`.

### Worker Process (worker) - CLI
The artisan command class for the worker should extend `SMSkin\LaravelSupervisor\Commands\WorkerCommand`.

In the class, you need to implement two methods:
* `public function getSubscribedSignals(): array` - an array of PCNTL signals that this process listens for.
* `public function handleSignal(int $signal, false|int $previousExitCode = 0): int|false` - a method called when a PCNTL signal is received.

* You can find an example of an artisan command in `./src/Examples/TestWorkerCommand.php`.

### Worker Process Model
The class should implement the `SMSkin\LaravelSupervisor\Contracts\IWorker interface`.

In the class, you need to implement the method `public function getArtisanCommand(): string`, which returns the artisan command corresponding to the worker process.

You can find an example class in `./src/Examples/TestWorker.php`.

## Projects that use this library (for extended example):
* [smskin/laravel-rabbitmq](https://github.com/smskin/laravel-rabbitmq)
    * [Supervisor command](https://github.com/smskin/laravel-rabbitmq/blob/main/src/Commands/SupervisorCommand.php)
    * [Worker command](https://github.com/smskin/laravel-rabbitmq/blob/main/src/Commands/WorkerCommand.php)
    * [Worker model](https://github.com/smskin/laravel-rabbitmq/blob/main/src/Entities/Worker.php)
