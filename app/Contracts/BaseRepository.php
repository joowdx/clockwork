<?php

namespace App\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository implements Repository
{
    private Builder $builder;

    protected array $with = [];

    public function __construct(private readonly Model $model)
    {
        $this->builder = $this->model->newQuery();

        $this->init($this->builder);
    }

    /**
     * Initialize current builder.
     *
     * @param  Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function init(Builder &$builder): void { }

    /**
     * Returns a new model instance.
     *
     * @return Builder
     */
    protected function model(): Model
    {
        return $this->model->replicate();
    }

    /**
     * Alias of "getBuilder" method.
     *
     * @return Builder
     */
    protected function builder(): Builder
    {
        return $this->getBuilder();
    }

    /**
     * Returns the bulder.
     *
     * @return Builder
     */
    protected function getBuilder(): Builder
    {
        return $this->builder;
    }

    /**
     * Returns the builder if set to true and reinit.
     * Otherwise the result then reinit.
     *
     * @param bool  $builder
     * @return mixed
     */
    public function get(bool $builder = false): mixed
    {
        if ($this->with) {
            $this->builder()->with($this->with);
        }

        $result = $builder ? $this->builder->clone() : $this->builder->clone()->get();

        $this->builder = $this->model->newQuery();

        $this->init($this->builder);

        return $result;
    }

    public function with(...$relationship): self
    {
        $this->builder()->with(...$this->with ?? [], ...$relationship);

        return $this;
    }

    public function all(): Collection
    {
        return $this->model()->with($this->with ?? [])->get();
    }

    public function find(array|string $id, ?Closure $finder = null): Model|Collection
    {
        if ($finder) {
            return $finder($this->model(), $id);
        }

        return $this->model()->find($id);
    }

    public function search(string $query, bool $paginate = false, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $result = $this->model()->search($query);

        $result = $paginate ? $result->paginate($perPage) : $result->get();

        return $result->load($this->with);
    }

    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        return $this->get(true)->paginate($perPage);
    }

    public function create(array $payload, ?Closure $creator = null): Model
    {
        if ($creator){
            return $creator($payload);
        }

        return $this->model()->create($this->transformData($payload));
    }

    public function insert(array $payload, ?Closure $inserter = null): void
    {
        if ($inserter) {
            DB::transaction(fn() => $inserter($payload));

            return;
        }

        DB::transaction(function () use ($payload) {
            $this->model()->insert(collect($payload)->map(fn ($e) => [
                ...$this->generateUuid($this->hasUuidPrimaryKey()),
                ...$this->transformData($e),
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray());
        });
    }

    public function update(Model|EloquentCollection|array $model, array $payload, array $except = [], ?Closure $updater = null): void
    {
        if ($updater) {
            $updater($model, $payload);

            return;
        }

        $data = collect($this->transformData($payload))->except($except)->toArray();

        if ($model instanceof Model) {
            $model->update($data);
        } else if ($model instanceof EloquentCollection) {
            $model->toQuery()->update($data);
        } else {
            $this->model()->whereIn('id', $model)->update($data);
        }

    }

    public function upsert(array $payload, array $unique, array $except = [], ?Closure $upserter = null): void
    {
        if ($upserter) {
            DB::transaction(fn() => $upserter($payload));

            return;
        }

        DB::transaction(function () use ($payload, $unique, $except) {
            $this->model()->upsert(collect($payload)->map(fn ($e) => [
                ...$this->generateUuid($this->hasUuidPrimaryKey()),
                ...collect($this->transformData($e))->except($except)->toArray(),
            ])->toArray(), $unique);
        });
    }

    public function delete(Model $model, ?Closure $deleter = null): void
    {
        if ($deleter) {
            $deleter($model);

            return;
        }

        $this->deleting($model);

        $model->delete();
    }

    public function destroy(array $payload, ?Closure $destroyer = null): void
    {
        if ($destroyer) {
            DB::transaction(fn () => $destroyer($payload));

            return;
        }

        $this->destroying($payload);

        DB::transaction(fn () =>  $this->model()->destroy($payload));
    }

    public function truncate(?Closure $truncator = null): void
    {
        if ($truncator) {
            $truncator($this->model());

            return;
        }

        $this->model()->truncate();
    }

    public function query(?Closure $query = null): mixed
    {
        return $query ? $query($this->model()->with($this->with ?? [])) : $this->model()->with($this->with ?? []);
    }

    protected function deleting(Model $model): void {}

    protected function destroying(array $payload): void {}

    protected abstract function transformData(array $payload): array;

    private function generateUuid(bool $generate) {
        return $generate ? ['id' => str()->orderedUuid()] : [];
    }

    private function hasUuidPrimaryKey(): bool
    {
        return trait_exists(\App\Traits\HasUniversallyUniqueIdentifier::class)
            && in_array(\App\Traits\HasUniversallyUniqueIdentifier::class, class_uses_recursive(get_class($this->model())));
    }

}
