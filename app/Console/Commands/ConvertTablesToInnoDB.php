<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertTablesToInnoDB extends Command
{
    protected $signature = 'convert:innodb';
    protected $description = 'Convert all tables to InnoDB storage engine';

    public function handle()
    {
        $database = env('DB_DATABASE');
		
        $tables = DB::select("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = '{$database}' AND ENGINE != 'InnoDB'");

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;
            DB::statement("ALTER TABLE {$tableName} ENGINE=InnoDB");
            $this->info("Converted {$tableName} to InnoDB");
        }

        $this->info("All tables have been converted to InnoDB.");
    }
}
