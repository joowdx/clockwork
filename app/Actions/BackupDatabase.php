<?php

namespace App\Actions;

use App\Models\Backup;
use Exception;
use Illuminate\Support\Facades\Process;

class BackupDatabase
{
    /**
     * @throws Exception
     */
    public function __invoke(bool $throw = true): Backup
    {
        return $this->backup($throw);
    }

    /**
     * @throws Exception
     */
    public function backup(bool $throw = true): Backup
    {
        $dump = new Backup;

        try {
            $time = now();

            $file = $time->format('Y_m_d_His').'.dump';

            $path = base_path('database/backups/'.$file);

            $process = Process::forever()->env([
                'PGDATABASE' => env('DB_DATABASE'),
                'PGPASSWORD' => env('DB_PASSWORD'),
                'PGUSER' => env('DB_USERNAME'),
                'PGHOST' => env('DB_HOST'),
                'PGPORT' => env('DB_PORT'),
            ]);

            $process->run([
                'pg_dump',
                '-Fc',
                '-c',
                '-f', $path,
            ])->throw();

            $dump->file = $file;

            $dump->created_at = $time;

            $dump->size = filesize($path);

        } catch (Exception $exception) {
            $dump->exception = $exception->getMessage();

            if ($throw) {
                throw $exception;
            }
        } finally {
            $dump->save();
        }

        return $dump;
    }
}
