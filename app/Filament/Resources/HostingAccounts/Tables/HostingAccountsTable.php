<?php

namespace App\Filament\Resources\HostingAccounts\Tables;

use App\Models\HostingAccount;
use App\Services\Audit\AuditLogger;
use App\Services\Renewals\SuspensionService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HostingAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('domain_name')->label('Domain')->searchable(),
                TextColumn::make('user.name')->label('Customer')->searchable(),
                TextColumn::make('whm_username')->label('cPanel user')->searchable(),
                TextColumn::make('hostingPackage.name')->label('Plan')->searchable(),
                TextColumn::make('server_ip')->label('Server IP')->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('renewal_date')->date()->sortable(),
                TextColumn::make('suspended_at')->dateTime()->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active', 'suspended' => 'Suspended', 'creating' => 'Creating',
                    'failed' => 'Failed', 'manual_review' => 'Manual Review', 'terminated' => 'Terminated',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        TextInput::make('reason')->label('Suspension reason')->default('Payment overdue')->required(),
                    ])
                    ->visible(fn (HostingAccount $r) => $r->isActive() && auth()->user()->can('manage', $r))
                    ->action(function (HostingAccount $record, array $data) {
                        $ok = app(SuspensionService::class)->suspend($record, $data['reason']);
                        app(AuditLogger::class)->log('hosting.suspend', $record, description: $data['reason']);
                        self::notify($ok, 'Hosting suspended', 'Suspension failed — see logs');
                    }),

                Action::make('unsuspend')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (HostingAccount $r) => $r->isSuspended() && auth()->user()->can('manage', $r))
                    ->action(function (HostingAccount $record) {
                        $ok = app(SuspensionService::class)->unsuspend($record);
                        app(AuditLogger::class)->log('hosting.unsuspend', $record);
                        self::notify($ok, 'Hosting reactivated', 'Reactivation failed — see logs');
                    }),
            ]);
    }

    private static function notify(bool $ok, string $success, string $failure): void
    {
        Notification::make()
            ->title($ok ? $success : $failure)
            ->{$ok ? 'success' : 'danger'}()
            ->send();
    }
}
