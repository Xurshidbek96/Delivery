<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** O'zbek (lotin) tilida sana. Carbon'ning `uz` lokali kirillcha chiqaradi, shuning uchun o'zimiz formatlaymiz. */
final class UzDate
{
    private const MONTHS = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];

    private const WEEKDAYS = ['yakshanba', 'dushanba', 'seshanba', 'chorshanba', 'payshanba', 'juma', 'shanba'];

    /** 20-noyabr, juma */
    public static function long(CarbonInterface $date): string
    {
        return $date->day.'-'.self::MONTHS[$date->month - 1].', '.self::WEEKDAYS[$date->dayOfWeek];
    }
}
