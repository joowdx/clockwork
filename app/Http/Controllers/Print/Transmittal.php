<?php

namespace App\Http\Controllers\Print;

use Livewire\Component;

class Transmittal extends Component
{
    public $employees, $from, $to;

    public function render()
    {
        return view('print.transmittal');
    }
}
