<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;

trait HasNameAccessorAndFormatter
{
    public function getFillable(): array
    {
        return array_merge($this->fillable, ['name']);
    }

    public function getCasts(): array
    {
        $cast = ['name' => 'object'];

        if ($this->getIncrementing()) {
            return array_merge([$this->getKeyName() => $this->getKeyType()], $this->casts, $cast);
        }

        return array_merge($this->casts, $cast);
    }

    protected function getArrayableAppends(): array
    {
        $appends = array_merge($this->appends, ['name_format']);

        return $this->getArrayableItems(
            array_combine($appends, $appends)
        );
    }

    public function getNameFormatAttribute(): object
    {
        return (object) [
            'full' => $this->nameFormatFull(),
            'fullInitialMiddle' => $this->nameFormatFullInitialMiddle(),
            'fullStartLast' => $this->nameFormatFullStartLast(),
            'fullStartLastInitialMiddle' => $this->nameFormatFullStartLastInitialMiddle(),
            'short' => $this->nameFormatShort(),
            'shortInitialFirst' => $this->nameFormatShortInitialFirst(),
            'shortStartLast' => $this->nameFormatShortStartLast(),
            'shortStartLastInitialFirst' => $this->nameFormatShortStartLastInitialFirst(),
        ];
    }

    public function getFullNameAttribute(): string
    {
        return $this->nameFormatFull();
    }

    public function nameFormatFull(): string
    {
        $name = $this->name;

        return $this->removeExtraWhitespaces("$name->first " . @$name->middle ."  $name->last" . $this->prependString(@$name->extension, ', '));
    }

    public function nameFormatFullInitialMiddle(): string
    {
        $name = $this->name;

        return $this->removeExtraWhitespaces("$name->first {$this->initial(@$name->middle)} $name->last" . $this->prependString(@$name->extension, ', '));
    }

    public function nameFormatFullStartLast(): string
    {
        $name = $this->name;

        return $this->removeExtraWhitespaces("$name->last, $name->first " . @$name->middle . "{$this->prependString(@$name->extension, ', ')}");
    }

    public function nameFormatFullStartLastInitialMiddle(): string
    {
        $name = $this->name;

        return $this->removeExtraWhitespaces("$name->last, $name->first{$this->prependString($this->initial(@$name->middle))}{$this->prependString(@$name->extension, ', ')}");
    }

    public function nameFormatShort(): string
    {
        $name = $this->name;

        return $this->removeExtraWhitespaces("$name->first $name->last");
    }

    public function nameFormatShortInitialFirst(): string
    {
        $name = $this->name;

        return $this->removeExtraWhitespaces("{$this->initial($name->first)} $name->last");
    }

    public function nameFormatShortStartLast(): string
    {
        $name = $this->name;

        return $this->removeExtraWhitespaces("$name->last, $name->first");
    }

    public function nameFormatShortStartLastInitialFirst(): string
    {
        $name = $this->name;

        return $this->removeExtraWhitespaces("$name->last, {$name->first[0]}.");
    }

    public function scopeSortByName(Builder $builder): Builder
    {
        return $this->scopeSortByNameAsc($builder);
    }

    public function scopeSortByNameAsc(Builder $builder): Builder
    {
        return $builder
            ->orderBy('name->last')
            ->orderBy('name->first')
            ->orderBy('name->middle')
            ->orderBy('name->extension');
    }

    public function scopeSortByNameDesc(Builder $builder): Builder
    {
        return $builder
            ->orderBy('name->last', 'desc')
            ->orderBy('name->first', 'desc')
            ->orderBy('name->middle', 'desc')
            ->orderBy('name->extension', 'desc');
    }

    private function appendString(?string $string, string $append): string
    {
        return $string ? "$string{$append}"  : '';
    }

    private function initial(?string $string): string
    {
        return $string ? "{$string[0]}." : '';
    }

    private function prependString(?string $string, string $prepend = ' '): string
    {
        return $string ? "{$prepend}$string"  : '';
    }

    private function removeExtraWhitespaces(?string $string): string
    {
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}