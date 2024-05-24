<?php

namespace SMSkin\LaravelSupervisor\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;

abstract class WorkerCommand extends Command implements SignalableCommandInterface
{
}
