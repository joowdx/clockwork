<?php

namespace App\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;
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
    protected function init(Builder &$builder): void
    {
    }

    /**
     * Returns a new model instance.
     *
     * @return Builder
     */
    public function model(): Model
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
     * @param  bool  $builder
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
        if ($creator) {
            return DB::transaction(fn () => $creator($payload, collect($payload)->map(fn ($payload) => $this->transformData($payload))->toArray()));
        }

        return DB::transaction(fn () => $this->model()->create($this->transformData($payload)));
    }

    public function insert(array $payload, ?Closure $inserter = null): void
    {
        if ($inserter) {
            DB::transaction(fn () => $inserter($payload, collect($payload)->map(fn ($payload) => $this->transformData($payload))->toArray()));

            return;
        }

        DB::transaction(function () use ($payload) {
            $this->model()->insert(collect($payload)->map(fn ($e) => [
                ...$this->generateUuid(generate: $this->hasUuidPrimaryKey()),
                ...$this->transformData($e),
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray());
        });
    }

    public function update(Model $model, array $payload, array $except = [], ?Closure $updater = null): Model
    {
        if ($updater) {
            return DB::transaction(fn () => $updater($model, $payload, collect($payload)->map(fn ($payload) => $this->transformData($payload))->toArray()));
        }

        DB::transaction(fn () => $model->update(collect($this->transformData($payload))->except($except)->toArray()));

        return $model;
    }

    public function upsert(array $payload, array $unique = [], array $update = [], array $except = [], ?Closure $upserter = null): void
    {
        if ($upserter) {
            DB::transaction(fn () => $upserter($payload, collect($payload)->map(fn ($payload) => $this->transformData($payload))->toArray()));

            return;
        }

        DB::transaction(function () use ($payload, $unique, $update, $except) {
            $this->model()->upsert(collect($payload)->map(fn ($e) => [
                ...$this->generateUuid(generate: $this->hasUuidPrimaryKey()),
                ...collect($this->transformData($e))->except($except)->toArray(),
            ])->toArray(), $unique, $update);
        });
    }

    public function delete(Model $model, ?Closure $deleter = null): void
    {
        if ($deleter) {
            $deleter($model);

            return;
        }

        $this->deleting($model);

        DB::transaction(fn () => $model->delete());
    }

    public function destroy(array $payload, ?Closure $destroyer = null): void
    {
        if ($destroyer) {
            DB::transaction(fn () => $destroyer($payload));

            return;
        }

        $this->destroying($payload);

        DB::transaction(fn () => $this->model()->destroy($payload));
    }

    public function truncate(?Closure $truncator = null): void
    {
        if ($truncator) {
            $truncator($this->model());

            return;
        }

        $this->model()->truncate();
    }

    public function query(): mixed
    {
        $builder = $this->builder()->with($this->with ?? []);

        $this->builder = $this->model->newQuery();

        $this->init($this->builder);

        return $builder;
    }

    protected function deleting(Model $model): void
    {
    }

    protected function destroying(array $payload): void
    {
    }

    abstract protected function transformData(array $payload): array;

    protected function generateUuid(string $column = 'id', bool $generate = true)
    {
        return $generate ? [$column => str()->orderedUuid()] : [];
    }

    protected function hasUuidPrimaryKey(): bool
    {
        return trait_exists(\App\Traits\HasUniversallyUniqueIdentifier::class)
            && in_array(\App\Traits\HasUniversallyUniqueIdentifier::class, class_uses_recursive(get_class($this->model())));
    }
}
