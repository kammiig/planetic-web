<?php

namespace App\Services\DNS;

/**
 * Builds the default DNS record set for a newly provisioned domain
 * (Frontend Spec §20.3, Ticket 32). Only the website records (@ and www) are
 * proxied through Cloudflare; mail, control-panel and authentication records
 * are always DNS-only so email and cPanel access keep working.
 */
class DefaultDnsRecordBuilder
{
    /**
     * @return array<int, array{type: string, name: string, content: string, ttl: int, proxied: bool, priority?: int}>
     */
    public function build(string $domain, string $serverIp): array
    {
        $domain = strtolower($domain);
        $ttl = (int) config('cloudflare.dns_ttl', 1);
        $mailHost = config('cloudflare.mail_hostname', 'mail').'.'.$domain;

        return [
            // Website — proxied.
            ['type' => 'A', 'name' => '@', 'content' => $serverIp, 'ttl' => $ttl, 'proxied' => true],
            ['type' => 'CNAME', 'name' => 'www', 'content' => $domain, 'ttl' => $ttl, 'proxied' => true],

            // Mail / control panel — DNS only (never proxied).
            ['type' => 'A', 'name' => 'mail', 'content' => $serverIp, 'ttl' => $ttl, 'proxied' => false],
            ['type' => 'A', 'name' => 'cpanel', 'content' => $serverIp, 'ttl' => $ttl, 'proxied' => false],
            ['type' => 'A', 'name' => 'webmail', 'content' => $serverIp, 'ttl' => $ttl, 'proxied' => false],

            // Mail routing.
            ['type' => 'MX', 'name' => '@', 'content' => $mailHost, 'ttl' => $ttl, 'proxied' => false, 'priority' => 10],

            // Email authentication.
            ['type' => 'TXT', 'name' => '@', 'content' => $this->spfRecord($serverIp), 'ttl' => $ttl, 'proxied' => false],
            ['type' => 'TXT', 'name' => '_dmarc', 'content' => $this->dmarcRecord($domain), 'ttl' => $ttl, 'proxied' => false],
        ];
    }

    private function spfRecord(string $serverIp): string
    {
        return sprintf('v=spf1 +a +mx +ip4:%s ~all', $serverIp);
    }

    private function dmarcRecord(string $domain): string
    {
        $policy = config('cloudflare.dmarc_policy', 'none');

        return sprintf('v=DMARC1; p=%s; rua=mailto:%s', $policy, 'dmarc@'.$domain);
    }
}
