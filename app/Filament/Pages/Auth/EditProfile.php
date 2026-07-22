<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Information')
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                    ]),

                Section::make('Update Password')
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),

                // Only show App Settings to Admin users
                Section::make('Application Settings')
                    ->description('Global configurations for the AIWA Account Dashboard.')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Application Name')
                            ->default(config('app.name'))
                            ->dehydrated(false)
                            ->required(),
                    ])
                    ->visible(fn () => auth()->user()?->isAdmin()),

                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // If an admin updated the application name
        if ($record->isAdmin() && isset($this->data['app_name'])) {
            $newName = $this->data['app_name'];
            $this->updateAppNameInEnv($newName);
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function updateAppNameInEnv(string $newName): void
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $content = file_get_contents($path);
            
            // Replaces APP_NAME=... with APP_NAME="New Name"
            $content = preg_replace('/^APP_NAME=.*/m', 'APP_NAME="' . addslashes($newName) . '"', $content);
            
            file_put_contents($path, $content);
        }
    }
}
