<?php

namespace App\Http\Controllers;

use App\Http\Requests\SpecimenRequest;
use App\Models\Signature;
use App\Models\Specimen;
use Illuminate\Http\Request;

class SpecimenController extends Controller
{
    public function store(
        Signature $signature,
        SpecimenRequest $request
    ) {
        $samples = collect($request->samples)->map(fn ($e) => [
            'sample' => $e->get(),
            'mime' => $e->getMimeType(),
            'checksum' => hash_file('sha3-256', $e->getRealPath()),
        ]);

        $signature->specimens()->createMany($samples);

        return redirect()->back();
    }

    public function update(SpecimenRequest $request, Specimen $specimen)
    {
        $specimen->update($request->validated());

        return redirect()->back();
    }

    public function destroy(SpecimenRequest $request, Specimen $specimen)
    {
        $specimen->delete();

        return redirect()->back();
    }
}
