<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\Registrar\RegistrarInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class SyncDomainStatusesCommand extends Command
{
    protected $signature = 'domains:sync {--limit=100}';

    protected $description = 'Refresh domain expiry dates and status from the registrar.';

    public function handle(RegistrarInterface $registrar): int
    {
        $domains = Domain::where('status', 'active')
            ->whereNotNull('registrar_domain_id')
            ->orderBy('last_synced_at')
            ->limit((int) $this->option('limit'))
            ->get();

        $synced = 0;
        foreach ($domains as $domain) {
            try {
                $info = $registrar->getDomainInfo($domain->domain_name);

                $domain->update(array_filter([
                    'expiry_date' => $info['expiry_date'] ? Carbon::parse($info['expiry_date'])->toDateString() : null,
                    'nameservers' => $info['nameservers'] ?: null,
                    'last_synced_at' => now(),
                ]));
                $synced++;
            } catch (Throwable $e) {
                $this->warn("Could not sync {$domain->domain_name}: {$e->getMessage()}");
            }
        }

        $this->info("Synced {$synced} domain(s).");

        return self::SUCCESS;
    }
}
