<?php

namespace Voodflow\Voodflow\Console\Commands;

use Illuminate\Console\Command;

class VoodflowCommand extends Command
{
    public $signature = 'filament-signal';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
