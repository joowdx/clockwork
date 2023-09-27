<?php

namespace App\Http\Controllers;

use App\Models\Scanner;
use App\Services\ScannerService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request, ScannerService $scanner)
    {
        $category = in_array($request->category, ['office', 'group'])
            ? $request->category
            : 'office';

        $column = match($category) {
            'group' => 'groups',
            default => $category,
        };

        $subquery = fn (Builder $query) => $query
            ->selectRaw('distinct jsonb_array_elements(groups)::text as "g"')
            ->from('employees')
            ->when($category === 'group', fn ($q) => $q->whereRaw('jsonb_array_length("groups") > 0'))
            ->when($category === 'office', fn ($q) => $q->whereNotIn('office', ['']))
            ->when($request->search, fn ($q) => $q->where($column, 'ilike', "%$request->search%"));

        $query = DB::query()
            ->selectRaw("count(distinct employees.id) employees")
            ->selectRaw("string_agg(distinct scanners.name, ', ' order by scanners.name) scanners")
            ->fromSub($subquery, "groups")
            ->rightJoin('employees', 'employees.groups', '@>', DB::raw('"groups"."g"::jsonb'))
            ->leftJoin('enrollments', 'employees.id', 'enrollments.employee_id')
            ->leftJoin('scanners', 'scanners.id', 'enrollments.scanner_id')
            ->when($request->search, fn ($q) => $q->where($column, 'ilike', "%$request->search%"));

        return inertia('Attendance/Index', [
            'category' => $category,
            'search' => $request->search,
            'paginate' => $request->paginate ?? 25,
            'scanners' => [
                'assigned' => $scanner->get(),
                'all' => Scanner::search($request->scanner)
                    ->orderBy('name')
                    ->get(),
            ],
            ...match($request->category) {
                'group' => [
                    'groups' => Inertia::lazy(function () use ($request, $query) {
                        return $query
                            ->selectRaw("trim('\"' from g) as \"group\"")
                            ->selectRaw("string_agg(distinct office, ', ') offices")
                            ->whereRaw('jsonb_array_length("employees"."groups") > 0')
                            ->groupBy('group')
                            ->paginate($request->paginate ?? 25);
                    })
                ],
                default => [
                    'offices' => Inertia::lazy(function () use ($request, $query) {
                        return $query
                            ->addSelect('office')
                            ->selectRaw("string_agg(distinct trim('\"' from g), ', ') groups")
                            ->whereNotIn('office', [''])
                            ->groupBy('office')
                            ->paginate($request->paginate ?? 25);
                    })
                ],
            },
        ]);
    }
}
