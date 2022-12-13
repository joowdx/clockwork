<?php

namespace App\Contracts;

interface ScannerDriver
{
    public function getAttlogs(): array;

    public function getUsers(): array;
}
