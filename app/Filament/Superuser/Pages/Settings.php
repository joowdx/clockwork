<?php

namespace App\Filament\Superuser\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

use function Filament\authorize;

class Settings extends Page
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static ?int $navigationSort = PHP_INT_MAX;

    protected static ?string $navigationIcon = 'gmdi-tune-o';

    protected static string $view = 'filament.superuser.pages.settings';

    protected ?string $subheading = 'This is global settings for the application.';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        try {
            return authorize('viewAny', Setting::class)->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    public function mount(): void
    {
        $data = Setting::fetch()->toArray();

        $this->form->fill($data);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('update')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General Information')
                            ->columns(5)
                            ->schema([
                                Forms\Components\FileUpload::make('seal')
                                    ->columnSpan(1)
                                    ->visibility('public')
                                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file) => 'seal.'.$file->extension())
                                    ->imageEditor()
                                    ->avatar()
                                    ->required()
                                    ->maxSize(2048),
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('name')
                                        ->markAsRequired()
                                        ->rule('required'),
                                    Forms\Components\TextInput::make('address')
                                        ->markAsRequired()
                                        ->rule('required'),
                                    Forms\Components\TextInput::make('url')
                                        ->url(),
                                    Forms\Components\TextInput::make('email')
                                        ->rule('email'),
                                ])->columnSpan(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Privacy Policy')
                            ->schema([
                                Forms\Components\MarkdownEditor::make('pp')
                                    ->hiddenLabel(),
                            ]),
                        Forms\Components\Tabs\Tab::make('User Agreement')
                            ->schema([
                                Forms\Components\MarkdownEditor::make('ua')
                                    ->hiddenLabel(),
                            ]),
                    ]),
            ]);
    }

    public function save()
    {
        $data = collect($this->form->getState())->map(fn ($value, $key) => ['key' => $key, 'value' => $value]);

        Setting::set($data->values()->toArray());

        Notification::make()
            ->success()
            ->title('Settings updated')
            ->body('Changes have been saved.')
            ->send();
    }
}
