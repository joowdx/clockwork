<?php

namespace App\Http\Controllers;

use App\Services\PrintService;

class PrintController extends Controller
{
    public function __invoke(PrintService $print)
    {
        return view($print->view(), $print->data());
    }
}
