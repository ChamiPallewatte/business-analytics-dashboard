<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * After saving, redirect to the companies list under the updated tenant slug.
     * This prevents 404 if the company slug was changed along with the name.
     */
    protected function getRedirectUrl(): string
    {
        // Get the freshly saved record with the new slug
        $record = $this->getRecord()->fresh();

        return "/admin/{$record->slug}/companies";
    }
}
