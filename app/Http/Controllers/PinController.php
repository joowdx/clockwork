<?php

namespace App\Http\Controllers;

use App\Http\Requests\PinRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PinController extends Controller
{
    public function setup(Employee $employee, Request $request)
    {
        $action = in_array(strtolower($request->action), ['update', 'setup', 'reset'])
            ? $request->action
            : null;

        if ($action === 'setup' && ! empty($employee->pin)) {
            $action = 'update';
        } else if ($action === 'update' && empty($employee->pin)) {
            $action = 'setup';
        } else if ($action === 'reset' && empty($employee->pin)) {
            $action = 'setup';
        }

        $scanners = $employee->scanners()
            ->select(['scanners.id', 'name'])
            ->reorder()
            ->orderBy('priority', 'desc')
            ->where('enabled', true)
            ->orderBy('name')
            ->get();

        return inertia('Auth/PinSetup', [
            'employee' => $employee,
            'scanners' => $scanners->makeHidden(['pivot']),
            'action' => $action ?? (empty($employee->pin) ? 'setup' : 'update')
        ]);
    }

    public function check(Employee $employee, Request $request)
    {
        $request->validate(['pin' => 'required|string']);

        throw_unless(
            Hash::check($request->pin, $employee->pin),
            ValidationException::withMessages(['pin' => 'Incorrect pin.'])
        );

        if ($request->inertia()) {
            return redirect()->back();
        }
    }

    public function initialize(Employee $employee, PinRequest $request)
    {
        $employee->update(['pin' => $request->pin]);

        if ($request->inertia()) {
            return redirect()->route('query.search', ['employee' => $employee->id]);
        }
    }

    public function change(Employee $employee, PinRequest $request)
    {
        $employee->update(['pin' => $request->pin]);

        if ($request->inertia()) {
            return redirect()->route('query.search', ['employee' => $employee->id]);
        }
    }

    public function reset(Employee $employee, PinRequest $request)
    {
        $employee->update(['pin' => $request->pin]);

        if ($request->inertia()) {
            return redirect()->route('query.search', ['employee' => $employee->id]);
        }
    }
}
