<?php

namespace App\Console\Commands;

use App\Actions\BackupDatabase as DumpDatabaseAction;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dump:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps database to disk';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle(DumpDatabaseAction $dumper): void
    {
        $dumper->backup(throw: false);
    }
}
