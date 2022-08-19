<?php

namespace App\Http\Controllers;

use App\Services\PrintService;

class PrintController extends Controller
{
    public function __invoke(PrintService $print, string $by)
    {
        return view("print.{$by}", $print->data($by));
    }
}
