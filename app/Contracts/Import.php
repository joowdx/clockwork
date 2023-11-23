<?php

namespace App\Contracts;

use App\Models\Scanner;
use Illuminate\Http\UploadedFile;

interface Import
{
    public function parse(Scanner $scanner, UploadedFile|string $file): mixed;

    public function validate(UploadedFile|string $file): bool;

    public function error(): string;
}
