<?php

use App\Models\SeoMeta;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Converts the seeded marketing copy that hardcodes "£200" into the storefront
 * token ":price" (see App\Support\Copy), so one stored sentence renders in the
 * right currency in every regional storefront instead of advertising pounds to
 * a USD visitor.
 *
 * Only rows whose text still EXACTLY matches the original seeded default are
 * touched. Anything an admin has edited is left alone — a migration must never
 * overwrite someone's own words, and the untokenised text keeps working (it
 * just stays GBP-specific until they choose to add the token).
 *
 * Testimonials are deliberately excluded. A testimonial is a real customer's
 * quotation; substituting a different figure into it would attribute a price to
 * someone who never said it.
 */
return new class extends Migration
{
    /** @return array<int, array{table: string, column: string, match: string, replace: string}> */
    private function replacements(): array
    {
        return [
            [
                'table' => 'faqs',
                'column' => 'answer',
                'match' => 'Yes — with the £200 website package your domain and hosting are free for the first year. Renewal applies after the first year at standard rates.',
                'replace' => 'Yes — with the :price website package your domain and hosting are free for the first year. Renewal applies after the first year at standard rates.',
            ],
            [
                'table' => 'faqs',
                'column' => 'question',
                'match' => 'What is included in the £200 website?',
                'replace' => 'What is included in the :price website?',
            ],
            [
                'table' => 'site_settings',
                'column' => 'value',
                'match' => 'Get a Website for £200',
                'replace' => 'Get a Website for :price',
            ],
            [
                'table' => 'site_settings',
                'column' => 'value',
                'match' => 'Search your domain or start your £200 website today.',
                'replace' => 'Search your domain or start your :price website today.',
            ],
            [
                'table' => 'seo_metas',
                'column' => 'meta_title',
                'match' => 'Complete Bespoke Website for £200',
                'replace' => 'Complete Bespoke Website for :price',
            ],
            [
                'table' => 'seo_metas',
                'column' => 'meta_description',
                'match' => 'UK domains, fast cPanel hosting, and a complete bespoke website for £200 with a free domain and hosting for the first year — built, secured and managed for you by Planetic Web.',
                'replace' => 'UK domains, fast cPanel hosting, and a complete bespoke website for :price with a free domain and hosting for the first year — built, secured and managed for you by Planetic Web.',
            ],
            [
                'table' => 'seo_metas',
                'column' => 'meta_description',
                'match' => 'A complete, custom-built website for £200 including a free domain and hosting for the first year. Designed, built, secured and launched for you by Planetic Web.',
                'replace' => 'A complete, custom-built website for :price including a free domain and hosting for the first year. Designed, built, secured and launched for you by Planetic Web.',
            ],
        ];
    }

    public function up(): void
    {
        $this->apply(fn (array $row) => [$row['match'], $row['replace']]);
    }

    public function down(): void
    {
        $this->apply(fn (array $row) => [$row['replace'], $row['match']]);
    }

    /** @param  callable(array<string, string>): array{0: string, 1: string}  $direction */
    private function apply(callable $direction): void
    {
        foreach ($this->replacements() as $row) {
            [$from, $to] = $direction($row);

            DB::table($row['table'])
                ->where($row['column'], $from)
                ->update([$row['column'] => $to]);
        }

        // SEO and settings are cached indefinitely; stale copy would survive
        // the migration otherwise. (FAQs are read straight from the database.)
        cache()->forget(SeoMeta::CACHE_KEY);
        cache()->forget(SiteSetting::CACHE_KEY);
    }
};
