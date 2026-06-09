<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Generate an XML sitemap of the public, indexable pages. Built from named
     * routes so URLs stay correct across environments (driven by APP_URL).
     */
    public function sitemap(): Response
    {
        $pages = [
            ['route' => 'home', 'priority' => '1.0', 'freq' => 'weekly'],
            ['route' => 'website-package', 'priority' => '0.9', 'freq' => 'monthly'],
            ['route' => 'hosting.index', 'priority' => '0.9', 'freq' => 'weekly'],
            ['route' => 'domains.index', 'priority' => '0.9', 'freq' => 'weekly'],
            ['route' => 'contact', 'priority' => '0.6', 'freq' => 'monthly'],
            ['route' => 'legal.privacy', 'priority' => '0.3', 'freq' => 'yearly'],
            ['route' => 'legal.terms', 'priority' => '0.3', 'freq' => 'yearly'],
            ['route' => 'legal.renewal', 'priority' => '0.3', 'freq' => 'yearly'],
            ['route' => 'legal.refund', 'priority' => '0.3', 'freq' => 'yearly'],
        ];

        $today = now()->toDateString();

        $urls = '';
        foreach ($pages as $page) {
            $urls .= '  <url>'."\n"
                .'    <loc>'.e(route($page['route'])).'</loc>'."\n"
                .'    <lastmod>'.$today.'</lastmod>'."\n"
                .'    <changefreq>'.$page['freq'].'</changefreq>'."\n"
                .'    <priority>'.$page['priority'].'</priority>'."\n"
                .'  </url>'."\n";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$urls
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
