<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('assigned_admin_id')
                    ->relationship('assignedAdmin', 'name'),
                TextInput::make('ticket_number')
                    ->required(),
                TextInput::make('subject')
                    ->required(),
                TextInput::make('category'),
                Select::make('priority')
                    ->options(SupportTicketPriority::class)
                    ->default('normal')
                    ->required(),
                Select::make('status')
                    ->options(SupportTicketStatus::class)
                    ->default('open')
                    ->required(),
                DateTimePicker::make('closed_at'),
            ]);
    }
}
