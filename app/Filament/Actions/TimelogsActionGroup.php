<?php

namespace App\Filament\Actions;

use Filament\Actions\ActionGroup;

class TimelogsActionGroup extends ActionGroup
{
    public function __construct(?array $actions = null)
    {
        if (is_array($actions)) {
            $this->actions($actions);
        }
    }

    public static function make(?array $actions = null): static
    {
        $static = app(static::class, ['actions' => $actions]);

        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Timelogs');

        $this->button();

        $this->color('gray');

        $this->actions([
            ImportTimelogsAction::make()
                ->label('Import'),
            FetchTimelogsAction::make()
                ->label('Fetch'),
        ]);
    }
}
