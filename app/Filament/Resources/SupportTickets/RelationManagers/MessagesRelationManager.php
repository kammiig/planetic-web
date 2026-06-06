<?php

namespace App\Filament\Resources\SupportTickets\RelationManagers;

use App\Models\NotificationLog;
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
            Textarea::make('message')
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
            ->recordTitleAttribute('message')
            ->defaultSort('created_at', 'asc')
            ->columns([
                IconColumn::make('is_internal_note')
                    ->label('Internal')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-chat-bubble-left-right'),
                TextColumn::make('author.name')->label('From'),
                TextColumn::make('message')->wrap()->limit(200),
                TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Reply')
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    })
                    ->after(function (array $data) {
                        // Public replies trigger a customer email (wired in Phase 11).
                        if (empty($data['is_internal_note'])) {
                            NotificationLog::create([
                                'user_id' => $this->getOwnerRecord()->user_id,
                                'type' => 'support_ticket_reply',
                                'channel' => 'mail',
                                'recipient' => $this->getOwnerRecord()->user->email,
                                'subject' => 'Reply to your support ticket '.$this->getOwnerRecord()->ticket_number,
                                'status' => 'sent',
                                'sent_at' => now(),
                            ]);
                        }
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
