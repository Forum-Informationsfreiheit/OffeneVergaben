<?php

if (! function_exists('contains_decimal')) {
    function contains_decimal($value) {
        if ( strpos( $value, "." ) !== false ) {
            return ".";
        }
        if ( strpos( $value, "," ) !== false ) {
            return ",";
        }
        return false;
    }
}

if (! function_exists('convert_number_to_cents')) {
    /**
     * 100     => 10000
     * 100.00  => 10000
     * 100,00  => 10000
     * 100,000 => 10000
     *
     * 100.12  => 10012
     * 100.123 => 10012
     * 100.126 => 10012
     *
     * Convert a given number to "cents"
     * An integer including TWO decimal positions
     * Any excess decimals (position >= 3) will be CUT (not rounded!)
     *
     * @param $value
     * @return null|int
     */
    function convert_number_to_cents($value) {
        $value = str_replace(',','.',$value);

        if (!is_numeric($value)) {
            return null;
        }

        $decimalSep = contains_decimal($value);

        if (!$decimalSep) {
            return intval($value) * 100;
        }

        $arr = explode($decimalSep,$value);

        $numberOfDecimals = strlen($arr[1]);

        if ($numberOfDecimals === 1) {
            return intval($arr[0].$arr[1]) * 10;
        }

        if ($numberOfDecimals === 2) {
            return intval($arr[0].$arr[1]);
        }

        // cut off the excess decimal
        return intval($arr[0].$arr[1][0].$arr[1][1]);
    }
}

if (! function_exists('ui_shorten')) {
    function ui_shorten($text, $length = 45) {
        if (!$text) {
            return $text;
        }

        if (strlen($text) > $length) {
            return substr($text,0,$length) . '...';
        } else {
            return $text;
        }
    }
}

if (! function_exists('nl_to_br')) {
    function nl_to_br($text) {
        if (!$text) {
            return $text;
        }

        return preg_replace("/(\r\n|\n|\r)/", "<br />", $text);
    }
}