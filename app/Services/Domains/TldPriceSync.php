<?php

namespace App\Services\Domains;

use App\Exceptions\RegistrarException;
use App\Models\TldPricing;
use App\Services\Registrar\RegistrarInterface;

/**
 * Syncs internal cost figures into the TLD price book from the active
 * registrar's wholesale pricing API (Porkbun). Only updates cost_price/markup
 * (admin reference) — the customer-facing register_price is never changed.
 */
class TldPriceSync
{
    public function __construct(private readonly RegistrarInterface $registrar) {}

    /**
     * @return array{synced: int, skipped: int, failed: array<int, string>, registrar: string}
     */
    public function sync(): array
    {
        $rate = (float) config('domain.usd_to_gbp', 0.79);
        $synced = 0;
        $skipped = 0;
        $failed = [];

        foreach (TldPricing::query()->orderBy('sort_order')->get() as $tld) {
            try {
                $pricing = $this->registrar->getPricing($tld->tld);
            } catch (RegistrarException $e) {
                $failed[] = $tld->tldLabel().': '.$e->getMessage();

                continue;
            }

            if (empty($pricing['supported']) || $pricing['registration'] === null) {
                $skipped++;

                continue;
            }

            $costGbp = round(((float) $pricing['registration']) * $rate, 2);

            $tld->update([
                'cost_price' => $costGbp,
                'markup' => round((float) $tld->register_price - $costGbp, 2),
                'cost_synced_at' => now(),
            ]);

            $synced++;
        }

        return [
            'synced' => $synced,
            'skipped' => $skipped,
            'failed' => $failed,
            'registrar' => $this->registrar->name(),
        ];
    }
}
