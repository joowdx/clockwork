<?php

namespace App\Http\Controllers\Print;

use Livewire\Component;

class Attlogs extends Component
{
    public $employee, $from, $to;

    public function render()
    {
        return view('print.attlogs');
    }
}
