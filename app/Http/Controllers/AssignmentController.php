<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignmentRequest;
use App\Models\Assignment;
use App\Repositories\ScannerRepository;
use App\Repositories\UserRepository;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        private AssignmentService $assignment,
        private UserRepository $user,
        private ScannerRepository $scanner,
    ) {
    }

    public function store(AssignmentRequest $request)
    {
        $request->whenHas('user', function () use ($request) {
            abort_unless(auth()->user()->administrator || $this->user->find($request->user)?->is(auth()->user()), 403);

            $this->assignment->sync($this->user->find($request->user), $request->scanners);
        })->whenHas('scanner', function () use ($request) {
            $scanner = $this->scanner->find($request->scanner);

            abort_unless(auth()->user()->administrator || $scanner?->createdBy?->is(auth()->user()) || $scanner->users->contains(auth()->user()), 403);

            $this->assignment->sync($this->scanner->find($request->scanner), $request->users);
        });

        return redirect()->back();
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        abort_unless(auth()->user()->administrator || $assignment->scanner?->createdBy?->is(auth()->user()), 403);

        $this->confirmPassword($request->password);

        $this->assignment->destroy($assignment);

        return redirect()->back();
    }
}
