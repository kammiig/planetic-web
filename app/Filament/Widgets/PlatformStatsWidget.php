<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProvisioningJob;
use App\Models\SupportTicket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $revenue = (float) Payment::where('status', PaymentStatus::Succeeded->value)->sum('amount');

        $failedProvisioning = ProvisioningJob::whereIn('status', ['failed', 'manual_review'])->count();

        $openTickets = SupportTicket::whereNotIn('status', ['resolved', 'closed'])->count();

        return [
            Stat::make('Revenue collected', '£'.number_format($revenue, 2))
                ->description('All successful payments')
                ->color('success'),

            Stat::make('Orders', (string) Order::count())
                ->description(Order::whereIn('status', ['provisioning', 'pending'])->count().' in progress')
                ->color('primary'),

            Stat::make('Provisioning failures', (string) $failedProvisioning)
                ->description($failedProvisioning > 0 ? 'Needs attention' : 'All clear')
                ->color($failedProvisioning > 0 ? 'danger' : 'success'),

            Stat::make('Open support tickets', (string) $openTickets)
                ->color($openTickets > 0 ? 'warning' : 'gray'),
        ];
    }
}
