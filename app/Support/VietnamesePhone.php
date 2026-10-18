<?php

namespace App\Support;

class VietnamesePhone
{
    public static function normalize(string $phone): string
    {
        $phone = preg_replace('/[\s.()\-]+/', '', $phone);

        return preg_match('/^0[35789][0-9]{8}$/D', $phone)
            ? '+84'.substr($phone, 1)
            : $phone;
    }

    /** Include legacy domestic numbers when checking duplicates or signing in. */
    public static function variants(string $phone): array
    {
        $phone = self::normalize($phone);

        return preg_match('/^\+84[35789][0-9]{8}$/D', $phone)
            ? [$phone, '0'.substr($phone, 3)]
            : [$phone];
    }
}
