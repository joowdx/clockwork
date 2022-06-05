<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface Import
{
    public function parse(Request $request): void;

    public function validate(Request $request): bool;

    public function error(): string;
}
