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
    ) { }

    public function store(AssignmentRequest $request)
    {
        $request->whenHas('user', function () use ($request) {

            $this->assignment->attach($this->user->find($request->user), $request->scanners);

        })->whenHas('scanner', function () use ($request) {

            $this->assignment->attach($this->scanner->find($request->scanner), $request->users);

        });

        return redirect()->back();
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        $this->confirmPassword($request->password);

        $this->assignment->destroy($assignment);

        return redirect()->back();
    }
}
