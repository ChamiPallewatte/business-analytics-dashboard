<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.settings-page';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?int $navigationSort = 11;
    protected static ?string $title = 'System Settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function mount(): void
    {
        $this->form->fill([
            'company_name'     => Setting::get('company_name', 'AIWA AGENCY'),
            'company_address'  => Setting::get('company_address', "AIWA Agency HQ\nDubai, UAE"),
            'company_trn'      => Setting::get('company_trn', '100234567800003'),
            'smtp_host'        => Setting::get('smtp_host', ''),
            'smtp_port'        => Setting::get('smtp_port', ''),
            'smtp_username'    => Setting::get('smtp_username', ''),
            'smtp_password'    => Setting::get('smtp_password', ''),
            'smtp_encryption'  => Setting::get('smtp_encryption', 'tls'),
            'smtp_from_address'=> Setting::get('smtp_from_address', ''),
            'smtp_from_name'   => Setting::get('smtp_from_name', ''),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Section::make('Company & Invoice Details')
                    ->description('These details are printed on PDF Invoices and Reports.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->required(),
                            TextInput::make('company_trn')
                                ->label('Company TRN (Tax Registration Number)')
                                ->nullable(),
                        ]),
                        Textarea::make('company_address')
                            ->label('Company Address')
                            ->rows(3)
                            ->required(),
                    ]),

                Section::make('SMTP Configuration (Email Notification Settings)')
                    ->description('Set up SMTP details to automatically send service renewal alerts.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('smtp_host')
                                ->label('SMTP Host')
                                ->placeholder('smtp.mailtrap.io')
                                ->nullable(),
                            TextInput::make('smtp_port')
                                ->label('SMTP Port')
                                ->placeholder('2525')
                                ->nullable(),
                            TextInput::make('smtp_encryption')
                                ->label('SMTP Encryption')
                                ->placeholder('tls')
                                ->nullable(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('smtp_username')
                                ->label('SMTP Username')
                                ->nullable(),
                            TextInput::make('smtp_password')
                                ->label('SMTP Password')
                                ->password()
                                ->nullable(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('smtp_from_address')
                                ->label('From Email Address')
                                ->placeholder('noreply@aiwa.agency')
                                ->nullable(),
                            TextInput::make('smtp_from_name')
                                ->label('From Name')
                                ->placeholder('AIWA Agency')
                                ->nullable(),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Settings saved successfully.')
            ->success()
            ->send();
    }
}
