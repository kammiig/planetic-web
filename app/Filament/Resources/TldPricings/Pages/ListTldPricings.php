<?php

namespace App\Filament\Resources\TldPricings\Pages;

use App\Filament\Resources\TldPricings\TldPricingResource;
use App\Services\Domains\TldPriceSync;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListTldPricings extends ListRecords
{
    protected static string $resource = TldPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('syncPorkbun')
                ->label('Sync costs from Porkbun')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Fetch wholesale costs from the active registrar and update the internal cost/margin columns. Customer prices are not changed.')
                ->action(function (): void {
                    try {
                        $result = app(TldPriceSync::class)->sync();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Sync failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Cost sync complete')
                        ->body(sprintf(
                            'Updated %d, skipped %d via %s.%s',
                            $result['synced'],
                            $result['skipped'],
                            $result['registrar'],
                            $result['failed'] ? ' '.count($result['failed']).' failed.' : '',
                        ))
                        ->success()
                        ->send();
                }),
        ];
    }
}
