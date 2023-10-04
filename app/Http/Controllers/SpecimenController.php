<?php

namespace App\Http\Controllers;

use App\Http\Requests\SpecimenUploadRequest;
use App\Models\Signature;
use App\Models\Specimen;
use Illuminate\Http\Request;

class SpecimenController extends Controller
{
    public function store(
        Signature $signature,
        SpecimenUploadRequest $request
    ) {
        $samples = collect($request->samples)->map(fn ($e) => [
            'sample' => $e->get(),
            'mime' => $e->getMimeType(),
            'checksum' => hash_file('sha3-256', $e->getRealPath()),
        ]);

        $signature->specimens()->createMany($samples);

        return redirect()->back();
    }

    public function update(Specimen $specimen, Request $request)
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);

        $specimen->update($validated);

        return redirect()->back();
    }

    public function destroy(Specimen $specimen)
    {
        $specimen->delete();

        return redirect()->back();
    }
}
