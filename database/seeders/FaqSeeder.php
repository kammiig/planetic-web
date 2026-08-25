<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['home', 'Is the domain and hosting really free?', 'Yes — with the :price website package your domain and hosting are free for the first year. Renewal applies after the first year at standard rates.'],
            ['home', 'How fast will my website be ready?', 'Once you complete the short intake form and provide your content, our team begins straight away. Most sites are ready within a couple of weeks.'],
            ['home', 'Do you set up email and SSL?', 'Yes. We configure SSL, DNS and email records (SPF, DKIM, DMARC) for you automatically through Cloudflare and cPanel.'],
            ['home', 'Can I pay monthly for hosting?', 'Yes. Hosting plans are available monthly or yearly, and you can upgrade at any time.'],
            ['hosting', 'Can I upgrade my plan later?', 'Absolutely. You can move to a larger plan at any time from your dashboard and only pay the difference.'],
            ['hosting', 'Is SSL included?', 'Every hosting plan includes a free SSL certificate, installed and renewed automatically.'],
            ['hosting', 'Can you move my existing website across?', 'Yes — migration is included. Tell us where your site is hosted now and we will move the files, databases and email for you, then switch DNS once everything is tested and working. Please keep your current hosting running until we confirm the move is complete.'],
            ['hosting', 'Will my site go offline while I move to you?', 'No. We build a working copy on our servers first and only change your DNS once it is confirmed working, so visitors keep reaching your site throughout. DNS changes are made through Cloudflare, which propagates in minutes rather than days.'],
            ['hosting', 'Are my files and databases backed up?', 'Your hosting account is backed up on our platform, and you can take your own backup at any time from cPanel. If you ever need something restored, open a support ticket and we will do it for you.'],
            ['hosting', 'What uptime can I expect?', 'We target 99.9% uptime and monitor our servers continuously. Planned maintenance is scheduled outside UK business hours and we let you know in advance whenever it could affect your site.'],
            ['hosting', 'Is business email included?', 'Yes. Every plan includes mailboxes on your own domain, and we configure the SPF, DKIM and DMARC records for you so your mail is far less likely to land in spam. You can use webmail or connect Outlook, Apple Mail or your phone.'],
            ['hosting', 'Do I need to be technical to manage my hosting?', 'No. We handle DNS, SSL and email setup for you, and your dashboard covers the everyday things. cPanel is there when you want it for files, databases and mailboxes — and our support team will do it for you if you would rather not.'],
            ['hosting', 'Can I cancel my hosting?', 'Yes — hosting is not tied to a long contract. Cancel from your dashboard or by contacting support and your plan runs to the end of the term you have paid for. Refunds are handled under our Refund Policy.'],
            ['website-package', 'What is included in the :price website?', 'A complete, custom-built website, a free domain and hosting for the first year, SSL, DNS and email setup, and a mobile-friendly, fast design.'],
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
