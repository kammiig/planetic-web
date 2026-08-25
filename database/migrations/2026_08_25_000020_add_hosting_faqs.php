<?php

use App\Models\Faq;
use Illuminate\Database\Migrations\Migration;

/**
 * Seeds the extra hosting FAQs (migrations, downtime, backups, uptime, email,
 * self-service, cancellation) so the /hosting accordion ships with them.
 *
 * They live in a migration as well as FaqSeeder because deploys run
 * `migrate --force` and never re-run seeders — without this, production would
 * keep the two original questions. Each row is inserted only if an FAQ with the
 * same page + question does not already exist, so re-running is safe and an
 * admin's later edits are never overwritten.
 */
return new class extends Migration
{
    /**
     * @var array<int, array{0: string, 1: string}>
     */
    private array $faqs = [
        [
            'Can you move my existing website across?',
            'Yes — migration is included. Tell us where your site is hosted now and we will move the files, databases and email for you, then switch DNS once everything is tested and working. Please keep your current hosting running until we confirm the move is complete.',
        ],
        [
            'Will my site go offline while I move to you?',
            'No. We build a working copy on our servers first and only change your DNS once it is confirmed working, so visitors keep reaching your site throughout. DNS changes are made through Cloudflare, which propagates in minutes rather than days.',
        ],
        [
            'Are my files and databases backed up?',
            'Your hosting account is backed up on our platform, and you can take your own backup at any time from cPanel. If you ever need something restored, open a support ticket and we will do it for you.',
        ],
        [
            'What uptime can I expect?',
            'We target 99.9% uptime and monitor our servers continuously. Planned maintenance is scheduled outside UK business hours and we let you know in advance whenever it could affect your site.',
        ],
        [
            'Is business email included?',
            'Yes. Every plan includes mailboxes on your own domain, and we configure the SPF, DKIM and DMARC records for you so your mail is far less likely to land in spam. You can use webmail or connect Outlook, Apple Mail or your phone.',
        ],
        [
            'Do I need to be technical to manage my hosting?',
            'No. We handle DNS, SSL and email setup for you, and your dashboard covers the everyday things. cPanel is there when you want it for files, databases and mailboxes — and our support team will do it for you if you would rather not.',
        ],
        [
            'Can I cancel my hosting?',
            'Yes — hosting is not tied to a long contract. Cancel from your dashboard or by contacting support and your plan runs to the end of the term you have paid for. Refunds are handled under our Refund Policy.',
        ],
    ];

    public function up(): void
    {
        // Continue the existing hosting order rather than jumping to the front.
        $sortOrder = (int) Faq::where('page', 'hosting')->max('sort_order');

        foreach ($this->faqs as [$question, $answer]) {
            Faq::firstOrCreate(
                ['page' => 'hosting', 'question' => $question],
                ['answer' => $answer, 'is_active' => true, 'sort_order' => ++$sortOrder],
            );
        }
    }

    public function down(): void
    {
        Faq::where('page', 'hosting')
            ->whereIn('question', array_column($this->faqs, 0))
            ->delete();
    }
};
