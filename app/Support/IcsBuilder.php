<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Builds a minimal RFC-5545 .ics calendar invite for a project meeting, so the
 * customer (and developer) can add it to Google Calendar / Outlook / Apple
 * Calendar from the email or the dashboard without any third-party API.
 */
class IcsBuilder
{
    public static function event(string $uid, string $title, Carbon $start, int $durationMinutes, ?string $description = null, ?string $url = null): string
    {
        $end = $start->copy()->addMinutes(max(1, $durationMinutes));
        $fmt = fn (Carbon $t) => $t->clone()->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Planetic Web//Project Meeting//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$fmt(Carbon::now()),
            'DTSTART:'.$fmt($start),
            'DTEND:'.$fmt($end),
            'SUMMARY:'.self::escape($title),
        ];

        if (filled($description)) {
            $lines[] = 'DESCRIPTION:'.self::escape($description);
        }
        if (filled($url)) {
            $lines[] = 'URL:'.self::escape($url);
            $lines[] = 'LOCATION:'.self::escape($url);
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        // RFC 5545 uses CRLF line endings.
        return implode("\r\n", $lines)."\r\n";
    }

    private static function escape(string $value): string
    {
        return str_replace(["\\", ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $value);
    }
}
