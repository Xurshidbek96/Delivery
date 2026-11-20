<?php

namespace App\Support;

/** O'zbekiston telefon raqamlari: hammasi +998XXXXXXXXX ko'rinishida saqlanadi. */
final class Phone
{
    public static function normalize(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        return preg_match('/^998\d{9}$/', $digits) ? '+'.$digits : null;
    }

    public static function isValid(?string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /** +998 90 123 45 67 */
    public static function format(?string $phone): string
    {
        $n = self::normalize($phone);
        if ($n === null) {
            return (string) $phone;
        }

        return sprintf('+998 %s %s %s %s', substr($n, 4, 2), substr($n, 6, 3), substr($n, 9, 2), substr($n, 11, 2));
    }
}
