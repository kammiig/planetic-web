# Planetic Web — Launch QA Checklist

Work through this before going live. **Launch is not approved until every Must-have item passes.**
Record the result and any notes in the Status column.

## Environment & security

| # | Item | Status |
| --- | --- | --- |
| 1 | HTTPS active on planeticweb.com | ☐ Pass ☐ Fail |
| 2 | `APP_DEBUG=false` and `APP_ENV=production` | ☐ Pass ☐ Fail |
| 3 | `.env` is not publicly accessible; app files outside web root | ☐ Pass ☐ Fail |
| 4 | No API keys committed to the repository | ☐ Pass ☐ Fail |
| 5 | Stripe test keys replaced with live keys | ☐ Pass ☐ Fail |
| 6 | Stripe webhook configured + signature verification on | ☐ Pass ☐ Fail |
| 7 | Admin passwords strong; admin 2FA enabled or planned | ☐ Pass ☐ Fail |
| 8 | Login + domain-search rate limiting active | ☐ Pass ☐ Fail |
| 9 | Customer ownership checks confirmed (cannot access others’ records) | ☐ Pass ☐ Fail |
| 10 | Support internal notes hidden from customers | ☐ Pass ☐ Fail |
| 11 | File uploads restricted; dangerous types blocked | ☐ Pass ☐ Fail |
| 12 | WHM / Cloudflare / registrar tokens stored securely & scoped | ☐ Pass ☐ Fail |
| 13 | SPF / DKIM / DMARC records configured for mail | ☐ Pass ☐ Fail |
| 14 | Queue cron + scheduler cron running | ☐ Pass ☐ Fail |
| 15 | Provisioning failures logged; admin can retry | ☐ Pass ☐ Fail |
| 16 | Audit logging enabled; database backups enabled | ☐ Pass ☐ Fail |

## Public site (Must-have)

| # | Item | Status |
| --- | --- | --- |
| 17 | Home, Website Package, Hosting, Domain Search, Contact, all Legal pages load | ☐ Pass ☐ Fail |
| 18 | Mobile responsive; keyboard accessible; visible focus states | ☐ Pass ☐ Fail |
| 19 | “Free domain and hosting for the first year. Renewal applies after the first year.” shown; no “free forever” | ☐ Pass ☐ Fail |
| 20 | Domain search returns available/unavailable + suggestions (aria-live) | ☐ Pass ☐ Fail |

## Customer flow (Must-have)

| # | Item | Status |
| --- | --- | --- |
| 21 | Registration + email verification | ☐ Pass ☐ Fail |
| 22 | Login / logout / password reset | ☐ Pass ☐ Fail |
| 23 | Add to cart (website / hosting / domain); server-side prices | ☐ Pass ☐ Fail |
| 24 | Checkout creates order + redirects to Stripe | ☐ Pass ☐ Fail |
| 25 | Stripe **test** payment completes | ☐ Pass ☐ Fail |
| 26 | Webhook marks order paid and starts provisioning (not the success page) | ☐ Pass ☐ Fail |
| 27 | Duplicate webhook does not double-provision | ☐ Pass ☐ Fail |
| 28 | Test domain registration (sandbox) | ☐ Pass ☐ Fail |
| 29 | Test WHM hosting account creation | ☐ Pass ☐ Fail |
| 30 | Test Cloudflare zone + DNS records (website proxied, mail DNS-only) | ☐ Pass ☐ Fail |
| 31 | Order confirmation + services-ready emails received | ☐ Pass ☐ Fail |
| 32 | Dashboard shows domains, hosting, invoices, renewal dates, project, tickets | ☐ Pass ☐ Fail |
| 33 | Website project intake form + secure file upload | ☐ Pass ☐ Fail |
| 34 | Support ticket create + reply | ☐ Pass ☐ Fail |

## Admin (Must-have)

| # | Item | Status |
| --- | --- | --- |
| 35 | `/admin` blocked for customers; Super Admin can log in | ☐ Pass ☐ Fail |
| 36 | Customers / Orders / Payments / Invoices visible | ☐ Pass ☐ Fail |
| 37 | Domains / Hosting / Cloudflare / DNS visible | ☐ Pass ☐ Fail |
| 38 | Provisioning monitor + manual retry works | ☐ Pass ☐ Fail |
| 39 | Hosting suspend / unsuspend works (WHM) | ☐ Pass ☐ Fail |
| 40 | Support reply + internal notes; audit logged | ☐ Pass ☐ Fail |

## Billing & renewals (Must-have)

| # | Item | Status |
| --- | --- | --- |
| 41 | Renewal reminder test (no duplicate for same day/service) | ☐ Pass ☐ Fail |
| 42 | Failed payment flow (order stays unpaid; retry available) | ☐ Pass ☐ Fail |
| 43 | Overdue hosting suspended only after grace period | ☐ Pass ☐ Fail |

## Automated tests

| # | Item | Status |
| --- | --- | --- |
| 44 | `php artisan test` passes | ☐ Pass ☐ Fail |

## Known issues / notes

_Document anything outstanding here before sign-off._
