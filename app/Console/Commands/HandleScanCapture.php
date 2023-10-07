<?php

namespace App\Console\Commands;

use App\Events\ScanCaptured;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class HandleScanCapture extends Command
{
    /**
     * Validation rules.
     *
     * @var array
     */
    protected $rules = [
        'ip_address' => 'required|string|ip|exists:scanners',
        'data' => 'required|string|json',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scanner:handle-capture { ip_address : IP address of the device } { data : The data being captured }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process captured fingerprint scans from scanners.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->validate();

        ScanCaptured::dispatch($this->argument('ip_address'), $this->argument('data'));
    }

    /**
     * Validate the arguments passed.
     */
    public function validate(): void
    {
        $validator = Validator::make($this->arguments(), $this->rules);

        collect($validator->errors()->all())->each(fn ($error) => $this->error($error));

        if ($validator->fails()) {
            exit;
        }
    }
}
