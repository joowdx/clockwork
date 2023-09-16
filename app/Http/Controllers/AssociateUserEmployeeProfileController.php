<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssociateUserEmployeeRequest;
use App\Models\Employee;
use App\Models\User;

class AssociateUserEmployeeProfileController extends Controller
{
    public function link(
        AssociateUserEmployeeRequest $request,
        User $user,
    ) {
        $user->employee()->associate($request->employee_id)->save();

        return redirect()->back();
    }

    public function unlink(
        AssociateUserEmployeeRequest $request,
        User $user,
        Employee $employee,
    ) {
        $user->employee()->dissociate()->save();

        return redirect()->back();
    }
}
