<?php

namespace Base33\FilamentSignal\Commands;

use Illuminate\Console\Command;

class FilamentSignalCommand extends Command
{
    public $signature = 'filament-signal';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
