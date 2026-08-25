<?php

use App\Models\Faq;
use Illuminate\Database\Migrations\Migration;

/**
 * Removes exact duplicate FAQ rows.
 *
 * The 2026_08_11 copy-tokenising migration rewrote "What is included in the
 * £200 website?" to the ":price" token form. Any environment that later re-ran
 * FaqSeeder — which matches on the raw question — then created a second,
 * identical row, and the page rendered the same question twice.
 *
 * Only rows identical in page, question AND answer are removed, and the oldest
 * of each set is kept, so an admin's edited copy is never the one deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Faq::query()
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Faq $faq) => implode('|', [
                $faq->page,
                $faq->getRawOriginal('question'),
                $faq->getRawOriginal('answer'),
            ]))
            ->each(function ($group) {
                if ($group->count() < 2) {
                    return;
                }

                Faq::whereIn('id', $group->skip(1)->pluck('id'))->delete();
            });
    }

    public function down(): void
    {
        // Deleting a duplicate is not reversible, and restoring one would only
        // reintroduce the repeated question.
    }
};
