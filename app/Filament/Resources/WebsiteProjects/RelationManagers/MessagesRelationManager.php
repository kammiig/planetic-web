<?php

namespace App\Filament\Resources\WebsiteProjects\RelationManagers;

use App\Mail\ProjectMessageMail;
use App\Models\WebsiteProjectMessage;
use App\Services\Notifications\NotificationService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversation';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Toggle::make('is_internal_note')
                ->label('Internal note (not visible to the customer)')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'asc')
            ->columns([
                IconColumn::make('is_internal_note')
                    ->label('Internal')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-chat-bubble-left-right'),
                TextColumn::make('author.name')->label('From'),
                TextColumn::make('body')->wrap()->limit(200),
                TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Reply')
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['is_from_staff'] = true;

                        return $data;
                    })
                    ->after(function (WebsiteProjectMessage $record) {
                        // Email the customer for public replies only.
                        if (! $record->is_internal_note) {
                            $project = $this->getOwnerRecord();
                            app(NotificationService::class)->send(
                                $project->user,
                                new ProjectMessageMail($project, $record),
                                'project_message',
                            );
                        }
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
