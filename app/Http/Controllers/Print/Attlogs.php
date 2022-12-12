<?php

namespace App\Http\Controllers\Print;

use Livewire\Component;

class Attlogs extends Component
{
    public $employee;

    public $from;

    public $to;

    public function render()
    {
        return view('print.attlogs');
    }
}
