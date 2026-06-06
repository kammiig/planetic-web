<?php

namespace App\Services\Hosting;

use App\Models\HostingPackage;
use App\Support\DomainName;
use Illuminate\Support\Str;

/**
 * Maps a public hosting package to its real WHM package name and generates
 * safe cPanel usernames/passwords. WHM usernames must be lowercase, start
 * with a letter and be at most 8 characters on most cPanel builds.
 */
class CpanelPackageMapper
{
    public function whmPackageFor(?HostingPackage $package): string
    {
        if ($package && filled($package->whm_package_name)) {
            return $package->whm_package_name;
        }

        return config('hosting.default_package', 'planetic_starter');
    }

    /**
     * Generate a unique, valid cPanel username derived from the domain.
     * Uniqueness is checked against existing hosting accounts.
     */
    public function generateUsername(string $domain): string
    {
        $sld = DomainName::parse($domain)->sld;
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $sld) ?: 'site');

        // Must start with a letter.
        if (! ctype_alpha($base[0] ?? 'x')) {
            $base = 'p'.$base;
        }

        $prefix = strtolower((string) config('whm.username_prefix', ''));
        $base = $prefix.$base;

        // Leave room for a numeric suffix within the 8-char limit.
        $stem = substr($base, 0, 6);

        do {
            $username = substr($stem.random_int(10, 99), 0, 8);
        } while (\App\Models\HostingAccount::where('whm_username', $username)->exists());

        return $username;
    }

    /** A strong, cPanel-acceptable password. */
    public function generatePassword(): string
    {
        return Str::password(18, symbols: true);
    }
}
