<?php

namespace Hac\Interfaces;

use Hac\Helpers\Command;

abstract class CommandsInterface extends Command
{
    protected string $signature;

    protected string $description;

    public function handle(): void
    {
    }
}
