<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Generate an XML sitemap of the public, indexable pages. Built from named
     * routes so URLs stay correct across environments (driven by APP_URL), and
     * driven by the admin SEO settings: any page flagged noindex is excluded
     * and each entry's lastmod tracks the SEO record's last update.
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
        $today = now()->toDateString();

        $urls = '';
        foreach ($pages as $page) {
            $meta = $seo->get($page['route']);

            // Respect the admin "discourage search engines" toggle.
            if ($meta?->noindex) {
                continue;
            }

            $lastmod = $meta?->updated_at?->toDateString() ?? $today;

            $urls .= '  <url>'."\n"
                .'    <loc>'.e(route($page['route'])).'</loc>'."\n"
                .'    <lastmod>'.$lastmod.'</lastmod>'."\n"
                .'    <changefreq>'.$page['freq'].'</changefreq>'."\n"
                .'    <priority>'.$page['priority'].'</priority>'."\n"
                .'  </url>'."\n";
        }

        // Published blog posts.
        foreach (\App\Models\Post::published()->get() as $post) {
            $urls .= '  <url>'."\n"
                .'    <loc>'.e(route('blog.show', $post->slug)).'</loc>'."\n"
                .'    <lastmod>'.$post->updated_at->toDateString().'</lastmod>'."\n"
                .'    <changefreq>monthly</changefreq>'."\n"
                .'    <priority>0.7</priority>'."\n"
                .'  </url>'."\n";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$urls
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
