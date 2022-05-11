<?php

namespace App\Repositories;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Contracts\BaseRepository;
use App\Contracts\UserRepository as RepositoryInterface;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use Laravel\Jetstream\Contracts\DeletesUsers;

class UserRepository extends BaseRepository implements RepositoryInterface
{
    public function create(array $payload, ?Closure $create = null): Model
    {
        return parent::create($payload, fn($info) => (new CreateNewUser)->create($info));
    }

    public function updateProfile(Model $user, array $payload): void
    {
        $this->update($user, $payload, updater: function($user, $payload) {
            app(UpdateUserProfileInformation::class)->update($user, $payload);
        });
    }

    public function updatePassword(Model $user, array $password): void
    {
        (new UpdateUserPassword)->update($user, $password, false);
    }

    public function removeProfilePhoto(Model $user): void
    {
        $user->deleteProfilePhoto();
    }

    public function delete(Model $model, ?Closure $deleter = null): void
    {
        parent::delete($model, fn($user) => app(DeletesUsers::class)->delete($user));
    }

    public function sessions(Model $user): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return collect(
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function ($session) {
            $agent = $this->createAgent($session);

            return (object) [
                'agent' => [
                    'is_desktop' => $agent->isDesktop(),
                    'platform' => $agent->platform(),
                    'browser' => $agent->browser(),
                ],
                'ip_address' => $session->ip_address,
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        });
    }

    public function deleteOtherSessionRecords(Model $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->delete();
    }

    public function sortByUsername(string $order = 'asc'): self
    {
        $this->builder()->orderBy('username', $order);

        return $this;
    }

    protected function createAgent($session): Agent
    {
        return tap(new Agent, function ($agent) use ($session) {
            $agent->setUserAgent($session->user_agent);
        });
    }

    protected function transformData(array $payload): array
    {
        return $payload;
    }

}
