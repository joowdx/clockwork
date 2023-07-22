<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UidSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->headers->set('Accept', 'application/json');

        $request->validate(['uid' => 'required|string|numeric']);

        return response()->json(
            Employee::query()
                ->whereHas('enrollments', fn ($q) => $q->whereUid($request->uid))
                ->with('scanners', fn ($q) => $q->select(['scanners.id', 'name'])->wherePivot('uid', $request->uid))
                ->select(['id', 'name', 'office', 'active'])
                ->orderBy('name->last')
                ->orderBy('name->first')
                ->orderBy('name->middle')
                ->orderBy('name->extension')
                ->get()
                ->map(fn ($employee) => [
                    'id' => $employee->id,
                    'office' => $employee->office,
                    'name' => ucwords(mb_strtolower($employee->name_format->fullStartLastInitialMiddle)),
                    'active' => (bool) $employee->active,
                    'scanners' => $employee->scanners->map(fn ($scanner) => ['id' => $scanner->id, 'name' => $scanner->name]),
                ]),
            200,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
            JSON_UNESCAPED_UNICODE
        );
    }
}
