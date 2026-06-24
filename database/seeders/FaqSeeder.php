<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['home', 'Is the domain and hosting really free?', 'Yes — with the £200 website package your domain and hosting are free for the first year. Renewal applies after the first year at standard rates.'],
            ['home', 'How fast will my website be ready?', 'Once you complete the short intake form and provide your content, our team begins straight away. Most sites are ready within a couple of weeks.'],
            ['home', 'Do you set up email and SSL?', 'Yes. We configure SSL, DNS and email records (SPF, DKIM, DMARC) for you automatically through Cloudflare and cPanel.'],
            ['home', 'Can I pay monthly for hosting?', 'Yes. Hosting plans are available monthly or yearly, and you can upgrade at any time.'],
            ['hosting', 'Can I upgrade my plan later?', 'Absolutely. You can move to a larger plan at any time from your dashboard and only pay the difference.'],
            ['hosting', 'Is SSL included?', 'Every hosting plan includes a free SSL certificate, installed and renewed automatically.'],
            ['website-package', 'What is included in the £200 website?', 'A complete, custom-built website, a free domain and hosting for the first year, SSL, DNS and email setup, and a mobile-friendly, fast design.'],
            ['website-package', 'What happens after the first year?', 'Your domain and hosting renew at our standard rates. We will always remind you before any renewal so there are no surprises.'],
        ];

        foreach ($faqs as $i => [$page, $question, $answer]) {
            Faq::firstOrCreate(
                ['page' => $page, 'question' => $question],
                ['answer' => $answer, 'is_active' => true, 'sort_order' => $i],
            );
        }
    }
}
