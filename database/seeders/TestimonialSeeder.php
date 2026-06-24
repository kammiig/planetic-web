<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            ['Sarah Thompson', 'Owner', 'Thompson Interiors', 'Planetic Web handled everything — domain, hosting and the whole website. I just sent my logo and content and they did the rest. Genuinely effortless.', 5],
            ['James Okafor', 'Director', 'Okafor Consulting', 'Fast, secure and beautifully designed. The £200 package was incredible value and the team set up my email and SSL without me lifting a finger.', 5],
            ['Priya Patel', 'Founder', 'Bloom Bakery', 'My site loads instantly and I have had zero downtime. Support is quick and friendly whenever I have a question. Highly recommended.', 5],
        ];

        foreach ($testimonials as $i => [$name, $role, $company, $body, $rating]) {
            Testimonial::firstOrCreate(
                ['author_name' => $name, 'company' => $company],
                ['author_role' => $role, 'body' => $body, 'rating' => $rating, 'is_active' => true, 'sort_order' => $i],
            );
        }
    }
}
