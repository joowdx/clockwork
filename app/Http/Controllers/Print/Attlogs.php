<?php

namespace App\Http\Controllers\Print;

use App\Http\Requests\PrintRequest;
use Livewire\Component;

class Attlogs extends Component
{
    public $employee;

    public $from;

    public $to;

    public function render(PrintRequest $request)
    {
        return view('print.attlogs', [
            'user' => $request->user(),
            'signature' => $request->sign ? $request->user()->randomSpecimen()?->toSrc() : null,
        ]);
    }
}
