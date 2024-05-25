<?php

namespace SMSkin\LaravelSupervisor\Contracts;

interface IWorker
{
    public function getArtisanCommand(): string;
}
