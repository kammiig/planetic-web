<?php

namespace App\Filament\Resources\WebsiteProjects\Pages;

use App\Enums\WebsiteProjectStatus;
use App\Filament\Resources\WebsiteProjects\WebsiteProjectResource;
use App\Models\WebsiteProject;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteProject extends EditRecord
{
    protected static string $resource = WebsiteProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Deliver: marks the project delivered and opens the revision window.
            Action::make('deliver')
                ->label('Deliver for review')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (WebsiteProject $record) => ! in_array($record->status, [
                    WebsiteProjectStatus::Delivered,
                    WebsiteProjectStatus::Completed,
                    WebsiteProjectStatus::Launched,
                    WebsiteProjectStatus::Cancelled,
                ], true))
                ->schema([
                    Select::make('revision_days')
                        ->label('Revision window')
                        ->options([7 => '7 days', 14 => '14 days', 30 => '30 days'])
                        ->default(14)
                        ->required(),
                ])
                ->action(function (WebsiteProject $record, array $data) {
                    $record->update([
                        'status' => WebsiteProjectStatus::Delivered->value,
                        'delivered_at' => now(),
                        'revision_days' => (int) $data['revision_days'],
                        'revisions_reopened_until' => null,
                    ]);
                }),

            // Reopen revisions after the window has closed.
            Action::make('reopenRevisions')
                ->label('Reopen revisions')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (WebsiteProject $record) => $record->delivered_at !== null && $record->revisionWindowHasEnded())
                ->schema([
                    TextInput::make('days')->label('Reopen for (days)')->numeric()->default(7)->required(),
                ])
                ->action(function (WebsiteProject $record, array $data) {
                    $record->update([
                        'revisions_reopened_until' => now()->addDays((int) $data['days']),
                        'status' => WebsiteProjectStatus::Delivered->value,
                    ]);
                }),

            DeleteAction::make(),
        ];
    }
}
