# Базовая библиотека для реализации дочерних процессов в Laravel

Идея данной библиотеки родилась в ходе изучения исходников [Laravel Horizon](https://laravel.com/docs/11.x/horizon).

Horizon запускает мастер-процесс, который порождает дочерние процессы для исполнения задач из очередей.

## Принцип работы
Мы запускаем мастер-процесс (`supervisor`), который порождает и контролирует дочерние процессы (`worker`). 

Мастер процесс подписан на сигналы [PCNTL](https://www.php.net/manual/ru/intro.pcntl.php). Получив сигнал, он сначала завершает дочерние процессы, а потом останавливается сам.

## Использование

### Мастер-процесс (supervisor) - CLI

Класс artisan команды супервизора должен наследоваться от `SMSkin\LaravelSupervisor\Commands\SupervisorsCommand`.

В классе необходимо реализовать метод `protected function getWorkers(): Collection`, возвращающий коллекцию моделей рабочего процесса (классов, реализующих интерфейс `SMSkin\LaravelSupervisor\Contracts\IWorker`).

Пример artisan команды можно посмотреть в `./src/Examples/TestSupervisorCommand.php`.

### Рабочий процесс (worker) - CLI

Класс artisan команды рабочего процесса должен наследоваться от `SMSkin\LaravelSupervisor\Commands\WorkerCommand`.

В классе необходимо реализовать два метода:
* `public function getSubscribedSignals(): array` - массив PCNTL сигналов, которые прослушиваются данным процессом
* `public function handleSignal(int $signal, false|int $previousExitCode = 0): int|false` - метод, вызываемый при получении PCNTL сигнала

Пример artisan команды можно посмотреть в `./src/Examples/TestWorkerCommand.php`.

### Модель рабочего процесса (worker model)

Класс должен реализовывать интерфейс `SMSkin\LaravelSupervisor\Contracts\IWorker`.

В классе необходимо реализовать метод `public function getArtisanCommand(): string`, возвращающий artisan команду, которая соответствует рабочему процессу.

Пример класса можно посмотреть в `./src/Examples/TestWorker.php`.

## Проекты, которые используют данную библиотеку:

* [smskin/laravel-rabbitmq](https://github.com/smskin/laravel-rabbitmq)
  * [Supervisor command](https://github.com/smskin/laravel-rabbitmq/blob/main/src/Commands/SupervisorCommand.php)
  * [Worker command](https://github.com/smskin/laravel-rabbitmq/blob/main/src/Commands/WorkerCommand.php)
  * [Worker model](https://github.com/smskin/laravel-rabbitmq/blob/main/src/Entities/Worker.php)
