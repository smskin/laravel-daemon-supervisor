<?php

namespace SMSkin\LaravelSupervisor\Contracts;

interface IWorker
{
    public function getSignature(): string;

    public function getArtisanCommand(): string;
}
