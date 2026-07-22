<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Models\ClientDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents & Attachments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_type')
                    ->label('Document Type')
                    ->options([
                        'Trade License' => 'Trade License',
                        'Emirates ID' => 'Emirates ID',
                        'Contract' => 'Contract',
                        'Quotation' => 'Quotation',
                        'Agreement' => 'Agreement',
                        'Other' => 'Other',
                    ])
                    ->required(),

                FileUpload::make('file_path')
                    ->label('File Attachment')
                    ->directory('client-documents')
                    ->visibility('public')
                    ->required(),

                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('file_path')
                    ->label('File')
                    ->formatStateUsing(fn ($state) => basename($state))
                    ->url(fn ($record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),

                TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Document'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
