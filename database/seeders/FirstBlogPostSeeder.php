<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

/**
 * Seeds the first blog post. Safe to re-run: matches on slug and only
 * creates the post if it does not already exist (so admin edits are never
 * overwritten by a later deploy).
 *
 * Run with: php artisan db:seed --class=FirstBlogPostSeeder
 */
class FirstBlogPostSeeder extends Seeder
{
    public function run(): void
    {
        Post::firstOrCreate(
            ['slug' => 'how-much-does-a-website-cost-uk'],
            [
                'title' => 'How much does a website cost in the UK? (2026 guide)',
                'meta_title' => 'How Much Does a Website Cost in the UK? (2026 Guide)',
                'meta_description' => 'UK website costs in 2026: DIY builders from £10/month, freelancers £500–£3,000, agencies £2,000–£6,000 — plus the hidden costs nobody mentions upfront.',
                'excerpt' => 'A straight answer with real numbers: what freelancers, agencies and DIY builders actually charge in 2026, the hidden costs to watch for, and how to choose.',
                'is_published' => true,
                'published_at' => now(),
                'body' => <<<'MD'
A basic business website in the UK costs between £500 and £3,000 from a freelancer, £2,000 to £6,000 from an agency, or £10 to £30 a month on a DIY builder like Wix or Squarespace. Complex ecommerce and custom builds run £5,000 to £30,000 or more. Our own complete package is £200. The wide range is not because some people are being ripped off (though some are) — it is because "a website" can mean very different things.

Here is how the numbers break down, what actually drives the price, and the costs nobody mentions upfront.

## The four ways to get a website

### 1. DIY website builders — £10 to £30 a month

Wix, Squarespace and similar tools let you build a site yourself with drag-and-drop templates. Over three years, a £20/month plan costs £720 — more than many "expensive-looking" options.

Choose this if you enjoy tinkering, have time to learn, and your business only needs a simple online presence. Be honest with yourself about the time: most business owners we speak to spent several weekends on a builder site and were never quite happy with the result.

### 2. Freelancers — £500 to £3,000

A skilled freelancer will build a standard business site (home, about, services, contact) for £500 to £3,000 depending on experience and how custom the design is. Quality varies more than in any other category — some freelancers are better than agencies, some will disappear mid-project.

Choose this if you have a referral you trust, and check who will own the domain and hosting when the work is done (more on that below).

### 3. Agencies — £2,000 to £6,000 for a standard site

A regional UK agency typically charges £2,000 to £6,000 for a professional small-business site, and £5,000 to £30,000+ for ecommerce or custom functionality. You are paying for a team: designer, developer, project manager, and usually a proper discovery process.

Choose this if your website is central to how you make money — for example you sell online, take bookings at volume, or need integrations with other systems. For a five-page brochure site, an agency is usually more than you need.

### 4. Fixed-price packages — £150 to £500

Productised packages (like ours) offer a set scope for a set price. Ours is £200 for a complete bespoke site, with the domain and hosting free for the first year. The trade-off is scope: you get a defined set of pages and two rounds of revisions, not unlimited changes.

Choose this if you need a professional site without a project to manage. Do not choose this if you need ecommerce with hundreds of products, a booking engine, or custom software — a fixed-price brochure package is the wrong tool, including ours.

## What actually drives the price

- **Number of pages and content.** Who is writing the words? Content written for you costs more.
- **Custom design vs template.** A template restyled to your brand is cheaper than a ground-up design.
- **Functionality.** Contact forms are simple. Payments, bookings, member areas and integrations are not.
- **Who manages the project.** Discovery workshops and project managers are valuable on big builds, and baked into agency pricing whether you use them or not.

## The hidden costs nobody mentions

The build price is not the whole price. Budget for these every year:

| Cost | Typical UK price |
|---|---|
| Domain renewal | £9–£15/year |
| Hosting | £60–£300/year |
| Business email | £0–£60/year per inbox |
| Maintenance and updates | £0–£300+/year |
| SSL certificate | Should be free — walk away if charged |

Two things to check before you sign anything:

1. **Who owns the domain?** It should be registered to you or clearly transferable to you. Businesses regularly lose their domain because it was registered under a developer's account and the developer vanished.
2. **What does year two cost?** Any "free first year" offer (including ours) has renewal charges afterwards. A trustworthy provider tells you the renewal price upfront — ours are listed on our [renewal policy](/renewal-policy).

## So what should you actually pay?

- Simple online presence, you have spare time: a DIY builder, around £250/year all-in.
- Professional brochure site, no time or patience for DIY: a fixed-price package (£150–£500) or a referred freelancer (£500–£1,500).
- The website is your shop or booking system: a freelancer or agency, £2,000+. Do not cut corners here.
- Custom functionality or large ecommerce: an agency, £5,000+.

The most expensive website is the one that has to be built twice. Match the option to what the site needs to do, not to the lowest number on the page.

## Common questions

### Why do quotes for the same site vary so much?

Because "the same site" rarely is. One quote assumes a template, your content and no revisions; another assumes custom design, copywriting and a discovery workshop. Ask each provider exactly what is included and compare like with like.

### Is a monthly-payment website a good idea?

Sometimes. £30–£60/month "website subscription" services spread the cost, but check the total over three years and what happens if you stop paying — with many, you lose the site entirely.

### What does the £200 Planetic Web package include?

A complete bespoke website (design and build), your domain and hosting free for the first year, SSL, DNS and business email set up, and two rounds of revisions. After the first year, standard domain and hosting renewals apply — the prices are published before you buy. See the [website package](/website-package) for the full scope.

### How long does a website take to build?

DIY: as long as your patience lasts. Our package: around two weeks once we have your content. Freelancers: two to six weeks. Agencies: six weeks to six months depending on scope.
MD,
            ],
        );
    }
}
