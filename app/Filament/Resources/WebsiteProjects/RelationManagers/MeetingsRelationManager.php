<?php

namespace App\Filament\Resources\WebsiteProjects\RelationManagers;

use App\Mail\ProjectMeetingMail;
use App\Models\WebsiteProjectMeeting;
use App\Services\Notifications\NotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MeetingsRelationManager extends RelationManager
{
    protected static string $relationship = 'meetings';

    protected static ?string $title = 'Meetings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('scheduled_at')
                ->label('Confirmed time')
                ->seconds(false)
                ->required(),
            TextInput::make('duration_minutes')->numeric()->default(30),
            TextInput::make('meeting_url')->label('Meeting link (Google Meet / Zoom)')->url(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('topic')
            ->defaultSort('proposed_at', 'desc')
            ->columns([
                TextColumn::make('requester.name')->label('Requested by'),
                TextColumn::make('proposed_at')->label('Proposed')->dateTime(),
                TextColumn::make('scheduled_at')->label('Confirmed')->dateTime()->placeholder('—'),
                TextColumn::make('duration_minutes')->label('Mins')->suffix(' min'),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([
                // Confirm / reschedule sets the agreed time and emails an .ics invite.
                Action::make('confirm')
                    ->label('Confirm / reschedule')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        DateTimePicker::make('scheduled_at')->label('Confirmed time')->seconds(false)->required(),
                        Select::make('duration_minutes')->options([15 => '15', 30 => '30', 45 => '45', 60 => '60'])->default(30),
                        TextInput::make('meeting_url')->label('Meeting link')->url(),
                    ])
                    ->fillForm(fn (WebsiteProjectMeeting $record) => [
                        'scheduled_at' => $record->scheduled_at ?? $record->proposed_at,
                        'duration_minutes' => $record->duration_minutes,
                        'meeting_url' => $record->meeting_url,
                    ])
                    ->action(function (WebsiteProjectMeeting $record, array $data) {
                        $wasConfirmed = $record->isConfirmed();
                        $record->update([
                            'scheduled_at' => $data['scheduled_at'],
                            'duration_minutes' => $data['duration_minutes'],
                            'meeting_url' => $data['meeting_url'] ?? null,
                            'status' => 'confirmed',
                        ]);

                        app(NotificationService::class)->send(
                            $record->project->user,
                            new ProjectMeetingMail($record->fresh('project'), $wasConfirmed ? 'rescheduled' : 'confirmed'),
                            'project_meeting',
                        );
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (WebsiteProjectMeeting $record) {
                        $record->update(['status' => 'cancelled']);
                        app(NotificationService::class)->send(
                            $record->project->user,
                            new ProjectMeetingMail($record->fresh('project'), 'cancelled'),
                            'project_meeting',
                        );
                    }),
            ])
            ->toolbarActions([]);
    }
}
