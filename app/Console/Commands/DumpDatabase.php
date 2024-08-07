<?php

namespace App\Console\Commands;

use App\Models\Dump;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

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
    public function handle()
    {
        try {
            $dump = new Dump();

            $time = now();

            $file = $time->format('Y_m_d_His') . '.dump';

            $path = base_path('database/dumps/' . $file);

            $process = Process::forever()->env(['PGPASSWORD' => env('DB_PASSWORD')]);

            $process->run([
                'pg_dump',
                '-h', env('DB_HOST'),
                '-d', env('DB_DATABASE'),
                '-U', env('DB_USERNAME'),
                '-p', env('DB_PORT'),
                '-Fc',
                '-c',
                '-f', $path,
            ])->throw();

            $dump->file = $file;

            $dump->created_at = $time;

            $dump->size = filesize($path);

        } catch (Exception $exception) {
            $dump->exception = $exception->getMessage();
        } finally {
            $dump->save();
        }
    }
}
