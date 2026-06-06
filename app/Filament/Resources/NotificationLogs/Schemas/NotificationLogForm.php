<?php

namespace App\Filament\Resources\NotificationLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class NotificationLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('type')
                    ->required(),
                TextInput::make('channel')
                    ->required(),
                TextInput::make('recipient')
                    ->required(),
                TextInput::make('subject'),
                TextInput::make('status')
                    ->required(),
                DateTimePicker::make('sent_at'),
                DateTimePicker::make('failed_at'),
                Textarea::make('error_message')
                    ->columnSpanFull(),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
