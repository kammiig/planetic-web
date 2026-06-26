<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin-editable marketing copy with the current site content as the
 * starting point. Idempotent: only fills a key when it does not already exist,
 * so re-seeding never overwrites edits the admin has made.
 */
class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->settings() as $i => $row) {
            SiteSetting::firstOrCreate(
                ['key' => $row['key']],
                [
                    'group' => $row['group'],
                    'value' => $row['value'],
                    'type' => $row['type'] ?? 'text',
                    'label' => $row['label'],
                    'help' => $row['help'] ?? null,
                    'sort_order' => $i,
                ],
            );
        }
    }

    /** @return array<int, array<string, string>> */
    private function settings(): array
    {
        return [
            // ---- Homepage hero ----
            ['group' => 'hero', 'key' => 'hero.eyebrow', 'label' => 'Eyebrow text', 'value' => 'Premium Hosting, Domains & Websites'],
            ['group' => 'hero', 'key' => 'hero.title', 'label' => 'Headline', 'value' => 'Your website, domain & hosting — done for you.'],
            ['group' => 'hero', 'key' => 'hero.subtitle', 'type' => 'textarea', 'label' => 'Sub-headline', 'value' => 'Search a domain, choose a plan, and let Planetic Web register, host and configure everything automatically. Secure billing, DNS and support in one dashboard.'],
            ['group' => 'hero', 'key' => 'hero.cta_primary', 'label' => 'Primary button label', 'value' => 'Get a Website for £200'],
            ['group' => 'hero', 'key' => 'hero.cta_secondary', 'label' => 'Secondary button label', 'value' => 'View Hosting Plans'],

            // ---- Trust badges ----
            ['group' => 'trust', 'key' => 'trust.badge_1', 'label' => 'Trust badge 1', 'value' => 'Free SSL on every site'],
            ['group' => 'trust', 'key' => 'trust.badge_2', 'label' => 'Trust badge 2', 'value' => 'Cloudflare protected'],
            ['group' => 'trust', 'key' => 'trust.badge_3', 'label' => 'Trust badge 3', 'value' => 'cPanel hosting'],
            ['group' => 'trust', 'key' => 'trust.badge_4', 'label' => 'Trust badge 4', 'value' => 'Secure Stripe billing'],
            ['group' => 'trust', 'key' => 'stats.uptime', 'label' => 'Uptime stat', 'value' => '99.9%'],
            ['group' => 'trust', 'key' => 'stats.support', 'label' => 'Support stat', 'value' => '24/7'],
            ['group' => 'trust', 'key' => 'stats.sites', 'label' => 'Sites launched stat', 'value' => '500+'],

            // ---- Services / sections ----
            ['group' => 'sections', 'key' => 'services.title', 'label' => 'Services title', 'value' => 'Everything you need to get online'],
            ['group' => 'sections', 'key' => 'services.subtitle', 'type' => 'textarea', 'label' => 'Services subtitle', 'value' => 'One platform for domains, hosting, DNS and a bespoke website — fully managed.'],
            ['group' => 'sections', 'key' => 'hosting.title', 'label' => 'Hosting section title', 'value' => 'Simple, transparent hosting'],
            ['group' => 'sections', 'key' => 'hosting.subtitle', 'type' => 'textarea', 'label' => 'Hosting section subtitle', 'value' => 'Choose a plan that fits. Switch or upgrade any time.'],
            ['group' => 'sections', 'key' => 'features.title', 'label' => 'Why-us title', 'value' => 'Built for speed, security and peace of mind'],
            ['group' => 'sections', 'key' => 'features.subtitle', 'type' => 'textarea', 'label' => 'Why-us subtitle', 'value' => 'Every plan is engineered on enterprise infrastructure and backed by a team that handles the technical work for you.'],
            ['group' => 'sections', 'key' => 'testimonials.title', 'label' => 'Testimonials title', 'value' => 'Trusted by businesses across the UK'],
            ['group' => 'sections', 'key' => 'faq.title', 'label' => 'FAQ title', 'value' => 'Frequently asked questions'],
            ['group' => 'sections', 'key' => 'cta.title', 'label' => 'Final CTA title', 'value' => 'Ready to get online?'],
            ['group' => 'sections', 'key' => 'cta.subtitle', 'type' => 'textarea', 'label' => 'Final CTA subtitle', 'value' => 'Search your domain or start your £200 website today.'],

            // ---- Domain search section ----
            ['group' => 'domains', 'key' => 'domains.title', 'label' => 'Domain search title', 'value' => 'Find your perfect domain name'],
            ['group' => 'domains', 'key' => 'domains.subtitle', 'type' => 'textarea', 'label' => 'Domain search subtitle', 'value' => 'Search across .com, .co.uk, .net and more. Every domain includes free WHOIS privacy and automatic Cloudflare DNS.'],

            // ---- Contact details ----
            ['group' => 'contact', 'key' => 'contact.email', 'type' => 'email', 'label' => 'Contact email', 'value' => 'support@planeticweb.com'],
            ['group' => 'contact', 'key' => 'contact.phone', 'label' => 'Contact phone', 'value' => ''],
            ['group' => 'contact', 'key' => 'contact.address', 'type' => 'textarea', 'label' => 'Business address', 'value' => ''],
            ['group' => 'contact', 'key' => 'contact.hours', 'label' => 'Opening hours', 'value' => 'Mon–Fri, 9am–6pm'],

            // ---- Social links ----
            ['group' => 'social', 'key' => 'social.facebook', 'type' => 'url', 'label' => 'Facebook URL', 'value' => ''],
            ['group' => 'social', 'key' => 'social.twitter', 'type' => 'url', 'label' => 'X / Twitter URL', 'value' => ''],
            ['group' => 'social', 'key' => 'social.instagram', 'type' => 'url', 'label' => 'Instagram URL', 'value' => ''],
            ['group' => 'social', 'key' => 'social.linkedin', 'type' => 'url', 'label' => 'LinkedIn URL', 'value' => ''],

            // ---- Footer / company ----
            ['group' => 'footer', 'key' => 'footer.tagline', 'type' => 'textarea', 'label' => 'Footer tagline', 'value' => 'Domains, hosting, DNS and bespoke websites — built, secured and managed for you.'],
            ['group' => 'footer', 'key' => 'company.name', 'label' => 'Company name', 'value' => 'Planetic Web'],

            // ---- Checkout ----
            ['group' => 'checkout', 'key' => 'checkout.require_card_for_free_orders', 'type' => 'boolean', 'label' => 'Require a card on free (£0) orders', 'help' => 'When on, customers placing a free first-year order must save a card (via Stripe SetupIntent) for future renewals. When off, free orders complete without a card.', 'value' => '0'],
        ];
    }
}
