# Planetic Web

Automated **website, hosting, domain, DNS, billing, renewal and client-management** platform.

Customers search a domain, buy a complete bespoke website (£200) or hosting, pay securely through
Stripe, and — only after a **verified Stripe webhook** — the platform automatically registers the
domain (NameSilo), creates a Cloudflare DNS zone, points the registrar nameservers at Cloudflare,
provisions a cPanel account via WHM, creates the default DNS records, and emails the customer. Everything
is visible in a customer dashboard and a Filament admin panel.

> **Business rule:** the £200 website package includes **“Free domain and hosting for the first year.
> Renewal applies after the first year.”** — never marketed as free forever.

---

## Stack

| Layer | Technology |
| --- | --- |
| Framework | Laravel 13 (PHP 8.3+) |
| Frontend | Blade, Tailwind CSS v4, Alpine.js, Vite |
| Admin | Filament v4 |
| Database | MySQL 8+ (SQLite for local dev/tests) |
| Payments | Stripe Checkout + Webhooks |
| Domains | NameSilo API (primary), Namecheap API (backup) |
| Hosting | Namecheap WHM / cPanel API |
| DNS | Cloudflare API |
| Email | cPanel SMTP (Jellyfish filtering) |
| Queue / Schedule | Database queue + Laravel scheduler, both run via cPanel cron |

---

## Architecture at a glance

- **Service classes** wrap every third-party API (`app/Services/{Billing,Registrar,Hosting,DNS,Provisioning,Renewals,Notifications}`). Controllers never call third-party APIs directly.
- **Provisioning** runs as an idempotent, retryable queued chain (`app/Jobs/Provisioning`) orchestrated by `ProvisioningOrchestrator`; every step is logged in `provisioning_jobs` and visible/retryable in the admin panel.
- **Row-level security:** every customer-owned model uses the `BelongsToUser` trait; controllers scope queries to the owner (404 hides existence) and Laravel policies gate everything (including the Filament panel).
- **Webhook safety:** Stripe signatures are verified, event IDs stored (`stripe_events`) for idempotency, and **services are only provisioned after a verified webhook — never from the success page**.

```
Frontend → Laravel backend → Third-party API     (API keys never reach the browser)
```

---

## Local development

Requirements: PHP 8.3+, Composer, Node 18+, and the PHP extensions `pdo_sqlite`/`pdo_mysql`, `mbstring`, `openssl`, `gd`, `zip`, `curl`, `bcmath`, `fileinfo`, `tokenizer`, `xml`.

```bash
composer install
cp .env.example .env
php artisan key:generate

# Local dev can use SQLite + log mailer:
#   DB_CONNECTION=sqlite   (touch database/database.sqlite)
#   MAIL_MAILER=log
php artisan migrate --seed     # seeds roles, products, hosting packages, admin user
php artisan storage:link

npm install
npm run dev                    # or: npm run build
php artisan serve
```

Run the test suite (in-memory SQLite, no external calls — all integrations are mocked):

```bash
php artisan test
```

### Creating an admin user

The seeder creates a Super Admin from `ADMIN_EMAIL` and `ADMIN_PASSWORD` (or prints a generated
password). You can also create/promote staff at any time:

```bash
php artisan planetic:make-admin --email=you@planeticweb.com --role=super_admin
```

Roles: `super_admin`, `technical_admin`, `billing_manager`, `support_staff`, `website_developer`, `customer`.

---

## cPanel deployment

### 1. Folder structure

Upload the application **outside** the public web root, then point the domain’s document root at the
app’s `public/` folder:

```
/home/cpanelusername/planeticweb-app/          ← Laravel application (private)
/home/cpanelusername/planeticweb-app/public/   ← document root for planeticweb.com
```

In cPanel → *Domains* (or *Addon/Subdomains*), set the document root for `planeticweb.com` to
`/home/cpanelusername/planeticweb-app/public`. Application files (`.env`, `app`, `routes`, `database`,
`storage`) must **not** be publicly accessible.

### 2. Environment

Copy `.env.example` to `.env` and fill in real values (MySQL credentials, Stripe live keys + webhook
secret, NameSilo/Namecheap, WHM, Cloudflare, cPanel SMTP, operational emails). Then:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://planeticweb.com
SESSION_SECURE_COOKIE=true
```

### 3. Build & install

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
npm install
npm run build
```

If Node is not available on the server, run `npm install && npm run build` locally and upload the
compiled `public/build` directory.

### 4. Cron jobs (cPanel → Cron Jobs)

**Laravel scheduler** (drives renewal reminders, domain/hosting sync, provisioning retries):

```bash
* * * * * cd /home/cpanelusername/planeticweb-app && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Queue worker** (safety net for any queued jobs — cron-based, no Supervisor needed):

```bash
* * * * * cd /home/cpanelusername/planeticweb-app && /usr/local/bin/php artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
```

> **Provisioning runs synchronously by default** (`PROVISIONING_SYNC=true`), so a customer's
> services are created the instant the verified Stripe webhook arrives — **no queue worker is
> required** for provisioning. Keep the queue cron above for other background jobs, and the
> scheduler cron (which also runs `orders:provision --stuck` every 10 min to finish any order
> whose provisioning stalled). Set `PROVISIONING_SYNC=false` only if you run a dedicated worker.

### 5. Stripe webhook

In the Stripe dashboard, add a webhook endpoint pointing to:

```
https://planeticweb.com/webhooks/stripe
```

Subscribe to: `checkout.session.completed`, `payment_intent.succeeded`,
`payment_intent.payment_failed`, `invoice.paid`, `invoice.payment_failed`,
`customer.subscription.created/updated/deleted`. Copy the signing secret into `STRIPE_WEBHOOK_SECRET`.

### 6. WHM package names

The public hosting plans map to WHM packages in the `hosting_packages` table (seeded as
`planetic_starter`, `planetic_business`, `planetic_pro`, `planetic_agency`). Create matching packages in
WHM, or edit the mapping in the admin panel (Super Admin).

### 7. Provisioning & troubleshooting

If an order is stuck (payment taken but services not showing), use the built-in commands:

```bash
# Inspect an order: payment status, Stripe IDs, each provisioning step, service records, last error.
php artisan orders:debug ORD-10007

# Re-run provisioning for one order. Confirms the charge with Stripe, creates any missing
# service records, and completes only the outstanding steps (idempotent — never duplicates).
php artisan orders:provision ORD-10007

# If Stripe cannot confirm the charge automatically (e.g. the webhook never arrived AND the
# PaymentIntent id was lost), verify the payment in the Stripe dashboard first, then force it:
php artisan orders:provision ORD-10007 --mark-paid

# Batch self-heal every paid-but-incomplete order (also runs automatically every 10 min via cron).
php artisan orders:provision --stuck
```

Common causes of a stuck order and the fix:

| Symptom | Cause | Fix |
| --- | --- | --- |
| Order stays "pending", tabs empty | Stripe webhook not configured / wrong `STRIPE_WEBHOOK_SECRET` | Add the endpoint (step 5), set the secret, then `orders:provision ORD-xxxx` for past orders |
| Order "provisioning" but a service missing | A provisioning step failed (registrar/WHM/Cloudflare error or missing API key) | `orders:debug ORD-xxxx` to see the error; fix config; `orders:provision ORD-xxxx` to retry |
| Want to test the full flow without registrar/WHM/Cloudflare keys | — | Set `PROVISIONING_DRY_RUN=true` to simulate provisioning with Stripe test keys only |

All provisioning activity is logged to `storage/logs` (Stripe webhook received, payment confirmed,
domain/Cloudflare/WHM summaries, failures, duplicates skipped, order completed) — **never** API
tokens, passwords or card details.

### Redeploying

```bash
cd /home/cpanelusername/planeticweb-app
php artisan down
git pull            # or upload changes
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
npm ci && npm run build
php artisan up
```

---

## Backups & recovery

Back up daily and store **outside the public web root** (never downloadable without authentication):

- **MySQL database** — `mysqldump` daily, with retention.
- **Uploaded website project files** — `storage/app/website-projects` (private).
- **`.env`** — a secure copy kept off-server.
- **`storage/logs`** — for debugging.

Restore: recreate the database, import the dump, restore `storage/app`, restore `.env`, then run
`php artisan migrate --force` and the cache commands above. See
[`docs/LAUNCH-QA-CHECKLIST.md`](docs/LAUNCH-QA-CHECKLIST.md) before any major deployment.

---

## Security notes

- API keys live only in `.env` — never in the repository, frontend JavaScript, logs, or emails.
- Full card numbers / CVV are never stored (Stripe holds payment details).
- Customers can only access their own records; staff access is role-limited; audit logs are append-only.
- All provisioning jobs are idempotent and safe to retry.
- See the Security and Access document in `docs/` for the full security model.

---

## Launch checklist

Run through [`docs/LAUNCH-QA-CHECKLIST.md`](docs/LAUNCH-QA-CHECKLIST.md) before going live.
