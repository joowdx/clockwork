<?php

namespace App\Http\Controllers;

use App\Services\PrintService;

class PrintController extends Controller
{
    public function __construct(
        private PrintService $print
    ) { }

    public function __invoke()
    {
        return view($this->print->view(), $this->print->data());
    }
}
