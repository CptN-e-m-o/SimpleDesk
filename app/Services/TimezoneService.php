<?php

namespace App\Services;

use DateTime;
use DateTimeZone;
use Exception;

class TimezoneService
{
    private const PRIORITY_IDENTIFIERS = [
        'UTC',
        'Europe/London',       // (GMT+00:00 / GMT+01:00)
        'Europe/Berlin',       // (GMT+01:00 / GMT+02:00)
        'Europe/Moscow',       // (GMT+03:00)
        'Asia/Dubai',          // (GMT+04:00)
        'Asia/Tashkent',       // (GMT+05:00)
        'Asia/Almaty',         // (GMT+06:00)
        'Asia/Krasnoyarsk',    // (GMT+07:00)
        'Asia/Shanghai',       // (GMT+08:00)
        'Asia/Tokyo',          // (GMT+09:00)
        'Australia/Sydney',    // (GMT+10:00 / GMT+11:00)
        'America/Los_Angeles', // (GMT-08:00 / GMT-07:00)
        'America/Denver',      // (GMT-07:00 / GMT-06:00)
        'America/Chicago',     // (GMT-06:00 / GMT-05:00)
        'America/New_York',    // (GMT-05:00 / GMT-04:00)
        'America/Sao_Paulo',   // (GMT-03:00)
    ];

    private ?array $formattedTimezones = null;

    public function getUniqueFormattedList(): array
    {
        if ($this->formattedTimezones !== null) {
            return $this->formattedTimezones;
        }

        $processed = [];
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $this->processIdentifiers(self::PRIORITY_IDENTIFIERS, $now, $processed);

        $this->processIdentifiers(DateTimeZone::listIdentifiers(), $now, $processed);

        ksort($processed);

        return $this->formattedTimezones = $processed;
    }

    private function processIdentifiers(array $identifiers, DateTime $now, array &$processed): void
    {
        foreach ($identifiers as $identifier) {
            try {
                $timezone = new DateTimeZone($identifier);
                $offset = $timezone->getOffset($now);

                if (isset($processed[$offset])) {
                    continue;
                }

                $offsetPrefix = $offset < 0 ? '-' : '+';
                $offsetFormatted = gmdate('H:i', abs($offset));
                $prettyOffset = "(GMT{$offsetPrefix}{$offsetFormatted})";

                $processed[$offset] = [
                    'identifier' => $identifier,
                    'display' => "{$prettyOffset} ".str_replace('_', ' ', $identifier),
                ];
            } catch (Exception $e) {
                continue;
            }
        }
    }
}
