<?php

namespace App\Filament\Resources\ProvisioningJobs\Tables;

use App\Models\ProvisioningJob;
use App\Services\Audit\AuditLogger;
use App\Services\Provisioning\ProvisioningRetryService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProvisioningJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.order_number')->label('Order')->searchable(),
                TextColumn::make('user.name')->label('Customer')->searchable(),
                TextColumn::make('job_type')->label('Step')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('attempts')->formatStateUsing(fn ($state, ProvisioningJob $r) => "{$state}/{$r->max_attempts}"),
                TextColumn::make('error_message')->label('Error')->limit(40)->toggleable()->wrap(),
                TextColumn::make('failed_at')->dateTime()->since()->sortable()->toggleable(),
                TextColumn::make('completed_at')->dateTime()->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'running' => 'Running', 'completed' => 'Completed',
                    'failed' => 'Failed', 'retrying' => 'Retrying', 'manual_review' => 'Manual Review',
                ]),
                SelectFilter::make('job_type')->options([
                    'register_domain' => 'Register Domain', 'create_cloudflare_zone' => 'Create Cloudflare Zone',
                    'update_nameservers' => 'Update Nameservers', 'create_hosting_account' => 'Create Hosting Account',
                    'create_dns_records' => 'Create DNS Records', 'send_welcome_email' => 'Send Welcome Email',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Re-queue this provisioning step. Steps are idempotent and safe to retry.')
                    ->visible(fn (ProvisioningJob $record) => $record->canRetry() && auth()->user()->can('retry', $record))
                    ->action(function (ProvisioningJob $record) {
                        $ok = app(ProvisioningRetryService::class)->retry($record);

                        app(AuditLogger::class)->log(
                            'provisioning.retry',
                            $record,
                            description: 'Retried provisioning step '.$record->job_type->value,
                        );

                        Notification::make()
                            ->title($ok ? 'Provisioning step re-queued' : 'Step could not be retried')
                            ->{$ok ? 'success' : 'warning'}()
                            ->send();
                    }),
            ]);
    }
}
