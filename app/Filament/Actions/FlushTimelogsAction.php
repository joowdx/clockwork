<?php

namespace App\Filament\Actions;

use App\Jobs\FetchTimelogs;
use App\Models\Scanner;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class FlushTimelogsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
    }
}
