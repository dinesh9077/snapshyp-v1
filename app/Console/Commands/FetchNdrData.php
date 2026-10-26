<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchNdrData extends Command
{
    protected $signature = 'ndr:fetch';
    protected $description = 'Fetch NDR data (Disabled - no supported courier; Shadowfax NDRs arrive via webhook)';

    public function handle()
    {
        $this->warn('No supported courier for NDR fetch');
        return 0;
    }
}