<?php

namespace App\Http\Controllers;

use App\Http\Requests\PinRequest;
use App\Models\Employee;
use Illuminate\Http\Request;

class PinController extends Controller
{
    public function index(Employee $employee, Request $request)
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

    public function store(Employee $employee, PinRequest $request)
    {
        $employee->update(['pin' => $request->pin]);

        return redirect()->back();
    }

    public function update(Employee $employee, PinRequest $request)
    {
        $employee->update(['pin' => $request->pin]);

        return redirect()->back();
    }

    public function delete(Employee $employee, PinRequest $request)
    {
        $employee->update(['pin' => $request->pin]);

        return redirect()->back();
    }
}
