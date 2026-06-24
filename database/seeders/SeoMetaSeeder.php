<?php

namespace Database\Seeders;

use App\Models\SeoMeta;
use Illuminate\Database\Seeder;

/**
 * Seeds per-page SEO defaults keyed by route name. Idempotent (firstOrCreate),
 * so admin edits are preserved on re-seed.
 */
class SeoMetaSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['page_key' => 'home', 'label' => 'Homepage', 'meta_title' => 'Premium Hosting, Domains & Bespoke Websites', 'meta_description' => 'UK domains, fast cPanel hosting, and a complete bespoke website for £200 with a free domain and hosting for the first year — built, secured and managed for you by Planetic Web.'],
            ['page_key' => 'hosting.index', 'label' => 'Hosting plans', 'meta_title' => 'Fast, Secure cPanel Web Hosting', 'meta_description' => 'Reliable cPanel hosting with free SSL, Cloudflare DNS and automatic setup. Monthly or yearly plans for businesses of every size, fully managed by Planetic Web.'],
            ['page_key' => 'website-package', 'label' => 'Website package', 'meta_title' => 'Complete Bespoke Website for £200', 'meta_description' => 'A complete, custom-built website for £200 including a free domain and hosting for the first year. Designed, built, secured and launched for you by Planetic Web.'],
            ['page_key' => 'domains.index', 'label' => 'Domain search', 'meta_title' => 'Search & Register Your Domain Name', 'meta_description' => 'Find and register the perfect domain with free WHOIS privacy and automatic Cloudflare DNS. Search .com, .co.uk, .net, .org and more with Planetic Web.'],
            ['page_key' => 'contact', 'label' => 'Contact', 'meta_title' => 'Contact Planetic Web', 'meta_description' => 'Get in touch with the Planetic Web team about domains, hosting, bespoke websites, billing or support.'],
        ];

        foreach ($pages as $page) {
            SeoMeta::firstOrCreate(['page_key' => $page['page_key']], $page);
        }
    }
}
