<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| Driven by the Laravel scheduler, which is invoked every minute by the
| cPanel cron entry documented in the README:
|   * * * * * cd /path && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
*/

// Renewal reminders + suspend hosting overdue past the grace period.
Schedule::command('renewals:check')->dailyAt('09:00')->withoutOverlapping();

// Reconcile domain expiry/status from the registrar.
Schedule::command('domains:sync')->dailyAt('02:00')->withoutOverlapping();

// Reconcile hosting account statuses with WHM.
Schedule::command('hosting:sync')->dailyAt('03:00')->withoutOverlapping();

// Auto-retry transient provisioning failures (never manual-review steps).
Schedule::command('provisioning:retry-failed')->hourly()->withoutOverlapping();

// Self-heal: finish any paid order whose provisioning stalled, AND verify
// recent "pending" orders that reached Stripe against the Stripe API — rescuing
// payments whose webhook never arrived. Manual-review orders are left for a human.
Schedule::command('orders:provision --stuck')->everyTenMinutes()->withoutOverlapping();
