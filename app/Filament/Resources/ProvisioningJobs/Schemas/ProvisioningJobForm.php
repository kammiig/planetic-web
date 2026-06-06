<?php

namespace App\Filament\Resources\ProvisioningJobs\Schemas;

use App\Enums\ProvisioningJobType;
use App\Enums\ProvisioningStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProvisioningJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('job_type')
                    ->options(ProvisioningJobType::class)
                    ->required(),
                Select::make('status')
                    ->options(ProvisioningStatus::class)
                    ->required(),
                TextInput::make('attempts')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('max_attempts')
                    ->required()
                    ->numeric()
                    ->default(3),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
                DateTimePicker::make('failed_at'),
                Textarea::make('error_message')
                    ->columnSpanFull(),
                Textarea::make('request_payload')
                    ->columnSpanFull(),
                Textarea::make('response_payload')
                    ->columnSpanFull(),
            ]);
    }
}
