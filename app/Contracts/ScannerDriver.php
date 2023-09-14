<?php

namespace App\Contracts;

interface ScannerDriver
{
    public function getAttlogs(): array;

    public function getUsers(): array;

    public function getFormattedAttlogs(string $withScannerId = null): array;

    public function syncTime(): void;
}
