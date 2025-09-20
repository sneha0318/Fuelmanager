<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;

class ClearOldShifts extends Command
{
    protected $signature = 'shifts:clear-old';
    protected $description = 'Clear shifts that are older than the current day';

    public function handle()
    {
        $deleted = Shift::where('created_date', '<', now()->format('Y-m-d'))->delete();
        $this->info("Deleted $deleted old shifts.");
    }
}
