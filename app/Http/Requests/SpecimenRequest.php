<?php

namespace App\Http\Requests;

use App\Models\Specimen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SpecimenRequest extends FormRequest
{
    const MINIMUM = 3;

    public function messages()
    {
        return match ($this->method()) {
            'POST' => [
                'samples.required' => 'Specimen sample is required.',
            ],
            default => [],
        };
    }

    public function attributes()
    {
        return match ($this->method()) {
            'POST' => [
                'samples.*' => 'specimen :position',
            ],
            default => []
        };
    }

    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => [
                'samples' => [
                    'required',
                    'array',
                ],
                'samples.*' => [
                    'file',
                    'image',
                    'mimes:png,webp',
                    'max:4096',
                    'dimensions:min_width=64,max_width=2048,min_height=64,max_height=2048',
                    function ($_, $file, $fail) {
                        if (
                            Specimen::query()
                                ->where('checksum', hash_file('sha3-256', $file->getRealPath()))
                                ->exists()
                        ) {
                            $fail('The specimen :position already exists in the database.');
                        }
                    },
                ],
            ],
            'PUT', 'PATCH' => [
                'enabled' => 'required|boolean',
            ],
            default => []
        };
    }

    public function after(): array
    {
        return match ($this->method()) {
            'POST' => [
                function (Validator $validator) {
                    $files = collect($this->samples)->mapWithKeys(fn ($s) => [
                        $s->getClientOriginalName() => hash_file('sha3-256', $s->getRealPath()),
                    ]);

                    $duplicates = $files->duplicates();

                    if ($duplicates->isNotEmpty()) {
                        $dupes = $files->filter(fn ($c, $f) => $duplicates->contains($c) && ! $duplicates->has($f));

                        $copies = $duplicates
                            ->map(fn ($c, $f) => [$f, ...$dupes->filter(fn ($d) => $d === $c)->keys()->toArray()])
                            ->values();

                        $validator->errors()->add(
                            'samples',
                            "{$copies->count()} distinct samples have some duplicates uploaded the same time: ".json_encode($copies)
                        );
                    }

                    $required = self::MINIMUM - $this->user()->specimens()->count() - $files->count();

                    if ($required > 0) {
                        $validator->errors()->add('samples', "Please add $required more samples.");
                    }
                },
            ],
            'PUT', 'PATCH' => [
                function (Validator $validator) {
                    $specimen = $this->route('specimen');

                    if (! $this->enabled && $specimen->signature->specimens()->enabled()->count() <= self::MINIMUM) {
                        $validator->errors()->add('enabled', 'Must have at least 3 active specimens enabled.');
                    }
                },
            ],
            default => [
                function (Validator $validator) {
                    $specimen = $this->route('specimen');

                    if ($specimen->enabled && $specimen->signature->specimens()->enabled()->count() <= self::MINIMUM) {
                        $validator->errors()->add('delete', 'Must have at least 3 active specimens enabled.');
                    }
                },
            ],
        };
    }
}
