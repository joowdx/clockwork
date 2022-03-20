<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface Import
{
    public function parse(UploadedFile $file): void;
}
