<?php

namespace App\Helpers;

class MoneyHelper {
    public static function format($amount)
    {
        if($amount != null) {
            $formatted = '₹'. number_format($amount, 2);
            return str_replace('.00', '', $formatted);
        }

        return $amount;

    }
}