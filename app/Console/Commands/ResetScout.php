<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;

class ResetScout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets scout indexing.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        collect(File::allFiles(app()->basePath().'/app/Models'))
            ->filter(fn ($file) => substr($file, -4) === '.php')
            ->map(fn ($file) => substr($file->getRelativePathName(), 0, -4))
            ->map(fn ($file) => str_replace('/', '\\', $file))
            ->map(fn ($file) => app(app()->getNamespace().'Models\\'.$file))
            ->filter(fn ($model) => in_array(Searchable::class, class_uses($model)))
            ->each(fn ($model) => $this->call('scout:flush', ['model' => $model::class]))
            ->each(fn ($model) => $this->call('scout:import', ['model' => $model::class]));
    }
}
