<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchNdrData extends Command
{
    protected $signature = 'ndr:fetch';
    protected $description = 'Fetch NDR data (Disabled - Xpressbees removed)';

    public function handle()
    {
        $this->warn('This command is disabled. It was only for Xpressbees which is no longer supported.');
        $this->info('Only Delhivery (ID=1) and ShipMozo (ID=2) are supported.');
        return 0;
    }
}