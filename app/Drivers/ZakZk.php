<?php

namespace App\Drivers;

use App\Contracts\ScannerDriver;

class ZakZk implements ScannerDriver
{
    public function __construct()
    {

    }

    public function getAttlogs(): array
    {
        return [];
    }

    public function getUsers(): array
    {
        return [];
    }
}
