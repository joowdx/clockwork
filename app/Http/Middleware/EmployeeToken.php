<?php

namespace App\Http\Middleware;

use App\Helpers\CaesarShift;
use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EmployeeToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->route('employee');

        $token = $request->header('x-employee-token');

        if (empty($token)) {
            return redirect()->route('query.search', ['employee' => $employee?->id]);
        }

        abort_unless($this->check($employee, $token), 403);

        return $next($request);
    }

    /**
     * Verify if given credential matches the current employee.
     */
    protected function check(Employee $employee, string $pin): bool
    {
        return Hash::check(
            CaesarShift::cipher(base64_decode($pin), $employee?->id, true),
            $employee->pin,
        );
    }
}
