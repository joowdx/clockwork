<?php

namespace App\Http\Controllers\Print;

use Livewire\Component;

class Transmittal extends Component
{
    public $employees;

    public $from;

    public $to;

    public $dates;

    public $group;

    public $copies = 2;

    public function render()
    {
        return view('print.transmittal');
    }
}
