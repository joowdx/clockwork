<?php

namespace App\Http\Requests;

use App\Models\Specimen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SpecimenUploadRequest extends FormRequest
{
    public function attributes()
    {
        return [
            'samples.*' => 'specimen :position'
        ];
    }

    public function rules(): array
    {
        return [
            'samples' => [
                'required',
                'array',
            ],
            'samples.*' => [
                'file',
                'image',
                'mimes:png,webp',
                'max:4096',
                'dimensions:min_width=128,max_width=1024,min_height=128,max_height=1024',
                function ($_, $file, $fail) {
                    if (
                        Specimen::query()
                            ->where('checksum', hash_file('sha3-256', $file->getRealPath()))
                            ->exists()
                    ) {
                        $fail('Sample :position already exists.');
                    }
                },
            ]
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $files = collect($this->samples)->mapWithKeys(fn ($s) => [
                    $s->getClientOriginalName() => hash_file('sha3-256', $s->getRealPath())
                ]);

                $duplicates = $files->duplicates();

                if ($duplicates->isNotEmpty()) {
                    $dupes = $files->filter(fn ($c, $f) => $duplicates->contains($c) && ! $duplicates->has($f));

                    $copies = $duplicates
                        ->map(fn ($c, $f) => [$f, ...$dupes->filter(fn ($d) => $d === $c)->keys()->toArray()])
                        ->values();

                    $validator->errors()->add(
                        'samples',
                        "{$copies->count()} distinct samples have some duplicates uploaded the same time: " . json_encode($copies)
                    );
                }
            }
        ];
    }
}
