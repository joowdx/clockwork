<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Actions\Jetstream\DeleteUser;
use App\Enums\UserType;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = function ($query) use ($request) {
            $query->whereNot('id', $request->user()->id)
                ->orderBy('username');
        };

        return inertia('Users/Index', [
            'users' => User::search($request->search)
                ->query($query)
                ->paginate($request->paginate ?? 50)
                ->withQueryString()
                ->appends('query', null),
            'types' => collect(UserType::cases())
                ->reject(fn ($e) => $e->value === -1)
                ->mapWithKeys(fn ($e) => [UserType::from($e->value)->label() => $e->value]),
        ]);
    }

    public function store(Request $request, CreateNewUser $creator)
    {
        $user = $creator->create($request->all());

        return redirect()->back()->with('flash', [
            'user' => $user->fresh(),
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        UpdateUserProfileInformation $profileUpdater,
        ResetUserPassword $passwordReseter,
    ) {
        if ($request->except(['password', 'password_confirmation'])) {
            $profileUpdater->update($user, $request->except(['password', 'password_confirmation']));
        }

        if ($request->filled('password') || $request->filled('password_confirmation')) {
            $passwordReseter->reset($user, $request->only(['password', 'password_confirmation']));
        }

        return redirect()->back()->with('flash', [
            'user' => $user->fresh(),
        ]);
    }

    public function destroy(User $user, DeleteUser $deleter)
    {
        $deleter->delete($user);

        return redirect()->route('users.index');
    }
}
