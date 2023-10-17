<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuerySearchRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QueryController extends Controller
{
    public function search(QuerySearchRequest $request)
    {
        if ($request->isMethod('GET')) {
            $employee = Employee::find($request->employee);

            return inertia('Query/Search', match(empty($employee)) {
                true => [],
                default => [
                    'employee' => $employee->id,
                    'name' => $employee->name_format->shortStartLast,
                    'proceed' => ! empty($employee->pin)
                ]
            });
        }

        $employee = Employee::query()
            ->where('name->first', $request->name['first'])
            ->where('name->last', $request->name['last'])
            ->when(
                isset($request->name['middle']),
                fn ($q) => $q->where('name->middle', $request->name['middle']),
                fn ($q) => $q->where(fn ($q) => $q->whereNull('name->middle')->orWhere('name->middle', '')),
            )
            ->when(
                isset($request->name['extension']),
                fn ($q) => $q->where('name->extension', $request->name['extension']),
                fn ($q) => $q->where(fn ($q) => $q->whereNull('name->extension')->orWhere('name->extension', '')),
            )
            ->first();

        throw_if(empty($employee), ValidationException::withMessages(['employee' => 'Employee not found.']));

        $scanners = $employee->scanners()
            ->select(['scanners.id', 'name'])
            ->reorder()
            ->orderBy('priority', 'desc')
            ->where('enabled', true)
            ->orderBy('name')
            ->get();

        if ($employee && ! $request->inertia() && $request->expectsJson()) {
            return [
                'employee' => $employee,
                'scanners' => $scanners,
                'action' => $employee->pin ? 'proceed' : 'setup',
            ];
        }

        return redirect()->route('query.search', ['employee' => $employee->id]);
    }

    public function result(Request $request, Employee $employee)
    {
        if ($request->expectsJson() && ! $request->inertia()) {
            return [
                'employee' => $employee,
            ];
        }

        return inertia('Query/Result', [
            'employee' => $employee,
        ]);
    }
}
