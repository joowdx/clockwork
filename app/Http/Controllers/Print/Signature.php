<?php

namespace App\Http\Controllers\Print;

use App\Http\Requests\PrintRequest;
use Livewire\Component;

class Signature extends Component
{
    private $signature;

    private $landscape;

    private $portrait;

    public function mount(PrintRequest $request)
    {
        if (! $request->sign) {
            return;
        }

        $specimen = $request->user()->randomSpecimen();

        $sample = imagecreatefromstring($specimen->sample);

        $width = imagesx($sample);

        $height = imagesy($sample);

        $this->landscape = $width >= $height;

        $this->portrait = ! $this->landscape;

        $this->signature = $specimen?->toSrc();

        imagedestroy($sample);
    }

    public function render(PrintRequest $request)
    {
        return view('print.signature', [
            'signature' => $this->signature,
            'landscape' => $this->landscape,
            'portrait' => $this->portrait,
        ]);
    }
}
