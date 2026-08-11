<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Support\Region;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * XML sitemap of the public, indexable pages across every regional
     * storefront. Built from named routes so URLs stay correct across
     * environments (driven by APP_URL), and driven by the admin SEO settings:
     * any page flagged noindex is excluded and each entry's lastmod tracks the
     * SEO record's last update.
     *
     * Each URL carries xhtml:link alternates for its counterpart in the other
     * storefronts. Submitting both trees with reciprocal alternates is what
     * lets Google index the GBP and USD versions separately and show each to the
     * right audience — the sitemap and the layout's hreflang tags say the same
     * thing, which is what Google expects.
     */
    public function sitemap(): Response
    {
        $pages = [
            ['route' => 'home', 'priority' => '1.0', 'freq' => 'weekly'],
            ['route' => 'website-package', 'priority' => '0.9', 'freq' => 'monthly'],
            ['route' => 'hosting.index', 'priority' => '0.9', 'freq' => 'weekly'],
            ['route' => 'domains.index', 'priority' => '0.9', 'freq' => 'weekly'],
            ['route' => 'blog.index', 'priority' => '0.7', 'freq' => 'weekly'],
            ['route' => 'contact', 'priority' => '0.6', 'freq' => 'monthly'],
            ['route' => 'legal.privacy', 'priority' => '0.3', 'freq' => 'yearly'],
            ['route' => 'legal.terms', 'priority' => '0.3', 'freq' => 'yearly'],
            ['route' => 'legal.renewal', 'priority' => '0.3', 'freq' => 'yearly'],
            ['route' => 'legal.refund', 'priority' => '0.3', 'freq' => 'yearly'],
        ];

        $seo = SeoMeta::all()->keyBy('page_key');
        $regions = Region::all();
        $today = now()->toDateString();
        $urls = '';

        foreach ($pages as $page) {
            $meta = $seo->get($page['route']);

            // Respect the admin "discourage search engines" toggle. The SEO
            // record is shared across storefronts, so this hides the page from
            // every regional tree at once — Google requires the robots
            // directives for a page to be consistent across its alternates.
            if ($meta?->noindex) {
                continue;
            }

            $lastmod = $meta?->updated_at?->toDateString() ?? $today;

            foreach ($regions as $region) {
                $urls .= $this->url(
                    $region->route($page['route']),
                    $lastmod,
                    $page['freq'],
                    $page['priority'],
                    $this->alternates($regions, $page['route']),
                );
            }
        }

        // Published blog posts, in every storefront.
        foreach (Post::published()->get() as $post) {
            foreach ($regions as $region) {
                $urls .= $this->url(
                    $region->route('blog.show', $post->slug),
                    $post->updated_at->toDateString(),
                    'monthly',
                    '0.7',
                    $this->alternates($regions, 'blog.show', $post->slug),
                );
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n"
            .$urls
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * hreflang alternates for one logical page, plus x-default pointing at the
     * default storefront.
     *
     * @param  array<int, Region>  $regions
     */
    private function alternates(array $regions, string $routeName, mixed $parameters = []): string
    {
        $out = '';

        foreach ($regions as $region) {
            $out .= '    <xhtml:link rel="alternate" hreflang="'.e($region->hreflang())
                .'" href="'.e($region->route($routeName, $parameters)).'"/>'."\n";
        }

        return $out.'    <xhtml:link rel="alternate" hreflang="x-default" href="'
            .e(Region::default()->route($routeName, $parameters)).'"/>'."\n";
    }

    private function url(string $loc, string $lastmod, string $freq, string $priority, string $alternates): string
    {
        return '  <url>'."\n"
            .'    <loc>'.e($loc).'</loc>'."\n"
            .$alternates
            .'    <lastmod>'.$lastmod.'</lastmod>'."\n"
            .'    <changefreq>'.$freq.'</changefreq>'."\n"
            .'    <priority>'.$priority.'</priority>'."\n"
            .'  </url>'."\n";
    }
}
