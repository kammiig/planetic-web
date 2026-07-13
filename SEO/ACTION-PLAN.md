# Planetic Web — SEO Action Plan (July 2026)

## Honest diagnosis

The site's technical SEO was already good (sitemap, schema, canonicals, meta system). That is not why there are no clients. The real reasons: the domain is new with zero backlinks, there are only 5 indexable pages, no content targets what buyers actually search, and the site showed fake trust signals. "Web hosting" and "domain registration" keywords are unwinnable against GoDaddy/123-Reg — **the £200 website package is your only realistic SEO product**. Everything below is built around that.

Expect 3–6 months before SEO brings steady enquiries. Section 8 covers getting clients faster in the meantime.

---

## 1. Code changes already made (deploy these)

| File | Change |
|---|---|
| All public page views | Rewrote titles/descriptions to target real keywords ("small business website design UK", "how much does a website cost", "UK web hosting") |
| `home.blade.php` | Replaced fake "500+ Sites launched" stat with honest "~2 weeks Typical website build" |
| `layouts/public.blade.php` | Added default social-share image fallback |
| `public/images/og-default.png` | New 1200×630 share image (shows when links are shared on WhatsApp/LinkedIn/Facebook) |

After deploying, run on the server: `php artisan view:clear && php artisan cache:clear`

**Note:** admin-panel SEO Meta entries override these view defaults. Check the admin SEO section — if entries exist for these pages, update them to match the new titles or delete them.

## 2. Admin panel — do this week (critical)

1. **Deactivate the fake testimonials** (Sarah Thompson, James Okafor, Priya Patel). Fake Trustpilot/Google badges violate Google's guidelines and the UK Digital Markets, Competition and Consumers Act — fake reviews are now explicitly illegal in the UK. The section auto-hides when empty. Re-add real ones as you get clients.
2. If a `stats.sites` setting exists with "500+", clear it.
3. Change the homepage H1 (hero title setting) to something keyword-relevant, e.g. "Your website, domain & hosting — done for you" / accent line: "Complete small business websites from £200."

## 3. Week 1 — free essentials (1–2 hours total)

1. **Google Search Console** — search.google.com/search-console → add property `planeticweb.com` → verify via DNS (you control Cloudflare) → submit `https://planeticweb.com/sitemap.xml` → request indexing on all 5 pages.
2. **Bing Webmaster Tools** — imports from GSC in one click. Bing/DuckDuckGo traffic is small but free.
3. **Google Analytics 4** (or Cloudflare Web Analytics if you want cookie-free) — you need to know if visitors arrive and where they drop off.
4. **Google Business Profile** — only if you have a real UK address/phone. Do not use a fake one; verification will fail and it risks suspension.

## 4. Real trust signals (replaces the fake ones)

- Create a free **Trustpilot business profile** and a **Google Business Profile**. After every project, send the client both links.
- Add a **portfolio page** — even 2–3 real sites you've built (your own counts). "Show me your work" is the #1 question buyers have and the site currently shows none.
- Show a real founder name and photo on an **About page**. Anonymous companies convert poorly in this niche.

## 5. Keyword targets (from your keywords.csv, filtered for winnability)

| Priority | Keyword theme | Page |
|---|---|---|
| 1 | small business website design (UK) | /website-package (done) |
| 1 | how much does a website cost UK / website design cost | new blog post — highest-intent topic you can win |
| 2 | affordable website design UK / cheap website design | /website-package + blog |
| 2 | website maintenance services / wordpress maintenance | new service page — recurring revenue, low competition |
| 3 | web design and hosting packages | homepage (done) |
| ✗ | web hosting, ecommerce, seo company, app development london | skip — unwinnable or wrong service |

## 6. Content plan (the main growth lever — months 1–3)

The site needs a blog. **The Laravel app doesn't have one yet — I can build it (routes, model, admin CRUD, sitemap integration) as a next task.**

First 6 posts, in order:

1. How much does a website cost in the UK? (2026 breakdown) — answer honestly with real numbers, include competitors' pricing
2. Website design for small businesses: what you actually need (and don't)
3. .co.uk vs .com: which domain should a UK business choose?
4. What makes a good website? 9 things we check before launch
5. Do you need website maintenance? When to pay and when not to
6. DIY website builders vs hiring a developer: honest comparison

Rules: answer the question in the first paragraph, use real prices, one FAQ section per post (FAQPage schema), 2 posts/month minimum, internal link to /website-package in every post.

## 7. Backlinks & citations (months 1–3, ongoing)

- Free UK directories: Yell, FreeIndex, Cylex, Bark, Clutch, Trustpilot, Yelp UK — consistent name/address/phone everywhere.
- List the business on cPanel/WHMCS partner directories if applicable.
- Ask every client for a footer credit link ("Website by Planetic Web") — this is how young agencies build links.
- Answer web-design questions on Reddit (r/smallbusinessuk), IndieHackers, and UK Facebook business groups with a profile link — no spam, just useful answers.

## 8. Getting clients NOW (while SEO ramps up)

SEO will not feed you for the next 3 months. In parallel:

- **Direct outreach**: find UK small businesses with no website or a broken one (Google Maps listings without websites are a goldmine). Short personal email, link to your package page. 10/day.
- **Marketplaces**: Bark.com and Fiverr/Upwork UK filters — price-competitive at £200.
- **Google Ads**: even £5–10/day on "cheap website design uk" + "£200 website" converts at this price point. The landing page (/website-package) is already good.
- **Local**: every UK town has business networking groups (BNI, chamber of commerce, Facebook groups).

## 9. 90-day calendar

| When | What |
|---|---|
| Week 1 | Deploy code changes · remove fake reviews · GSC + GA4 + Bing · directories (top 5) |
| Week 2 | Portfolio + About pages · Trustpilot profile · start outreach (10/day) |
| Weeks 3–4 | Build blog · publish post #1 (website cost) · Google Ads test |
| Month 2 | Posts #2–3 · website maintenance service page · remaining directories · first real reviews |
| Month 3 | Posts #4–6 · review GSC data, double down on whatever is getting impressions |

## Measuring success

Check GSC weekly: impressions first (weeks 2–6), then clicks (months 2–3), then enquiries (months 3–6). If a page gets impressions but no clicks, rewrite its title/description. If it gets clicks but no enquiries, fix the page content.
