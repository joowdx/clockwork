<?php

namespace App\Console\Commands;

use App\Actions\DumpDatabase as DumpDatabaseAction;
use Illuminate\Console\Command;

class DumpDatabase extends Command
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
     */
    public function handle(DumpDatabaseAction $dumper)
    {
        $dumper->dump(throw: false);
    }
}
