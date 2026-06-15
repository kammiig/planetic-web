<?php

namespace App\Services\DNS;

/**
 * Builds the default DNS record set for a newly provisioned hosting domain
 * (Frontend Spec §20.3, Ticket 32). Website records (@/www) — and, per the
 * platform's reference zone, mail/webmail — are proxied through Cloudflare;
 * the MX exchangers are always DNS-only so inbound email keeps flowing to the
 * mail cluster. SPF/DMARC are added for deliverability.
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
        $proxyMail = (bool) config('cloudflare.proxy_mail_records', true);

        $records = [
            // Website — proxied.
            ['type' => 'A', 'name' => '@', 'content' => $serverIp, 'ttl' => $ttl, 'proxied' => true],
            ['type' => 'A', 'name' => 'www', 'content' => $serverIp, 'ttl' => $ttl, 'proxied' => true],

            // Mail / webmail web interfaces — proxied per the platform reference
            // (these A records only serve the web UI; delivery uses the MX set).
            ['type' => 'A', 'name' => 'mail', 'content' => $serverIp, 'ttl' => $ttl, 'proxied' => $proxyMail],
            ['type' => 'A', 'name' => 'webmail', 'content' => $serverIp, 'ttl' => $ttl, 'proxied' => $proxyMail],
        ];

        // Mail routing — one MX per platform exchanger (priorities 5/10/20 by
        // default), always DNS-only so inbound mail is delivered to the cluster.
        $priorities = config('cloudflare.mx_priorities', [5, 10, 20]);
        foreach (config('cloudflare.mx_hosts', []) as $i => $host) {
            $records[] = [
                'type' => 'MX',
                'name' => '@',
                'content' => $host,
                'ttl' => $ttl,
                'proxied' => false,
                'priority' => $priorities[$i] ?? (($i + 1) * 10),
            ];
        }

        // Email authentication — DNS-only.
        $records[] = ['type' => 'TXT', 'name' => '@', 'content' => $this->spfRecord($serverIp), 'ttl' => $ttl, 'proxied' => false];
        $records[] = ['type' => 'TXT', 'name' => '_dmarc', 'content' => $this->dmarcRecord($domain), 'ttl' => $ttl, 'proxied' => false];

        return $records;
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
