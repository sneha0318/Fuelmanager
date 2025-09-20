<?php

namespace App\Console;

use App\Console\Commands\ClearOldShifts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('shifts:clear-old')->daily();
    }

    protected $commands = [
        Commands\ClearOldShifts::class,
    ];
}