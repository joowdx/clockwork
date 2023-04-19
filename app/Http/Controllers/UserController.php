<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Actions\Jetstream\DeleteUser;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return inertia('Users/Index', [
            'users' => User::where('id', '<>', $request->user()->id)->paginate(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia('Users/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, CreateNewUser $creator)
    {
        $creator->create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(UpdateUserRequest $request, User $user)
    {
        return inertia('Users/Edit', [
            'updateUser' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(
        UpdateUserRequest $request,
        User $user,
        UpdateUserProfileInformation $profileUpdater,
        ResetUserPassword $passwordReseter,
    ) {
        if ($request->except(['password', 'current_password'])) {
            $profileUpdater->update($user, $request->except(['password', 'current_password']));
        }

        if ($request->filled('password') || $request->filled('confirm_password')) {
            $passwordReseter->reset($user, $request->only(['password', 'confirm_password']));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user, DeleteUser $deleter)
    {
        $deleter->delete($user);

        return redirect()->route('users.index');
    }
}
