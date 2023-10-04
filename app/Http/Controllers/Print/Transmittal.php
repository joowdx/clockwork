<?php

namespace App\Http\Controllers\Print;

use App\Http\Requests\PrintRequest;
use Livewire\Component;

class Transmittal extends Component
{
    public $employees;

    public $from;

    public $to;

    public $dates;

    public $copies = 2;

    public function render(PrintRequest $request)
    {
        return view('print.transmittal', [
            'group' => request()->filled('groups'),
            'user' => $request->user(),
            'signature' => $request->sign ? $request->user()->randomSpecimen()?->toSrc() : null,
        ]);
    }
}
