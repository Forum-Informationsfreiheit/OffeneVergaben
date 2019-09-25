<?php

if (! function_exists('link_to_stylesheet')) {
    /**
     * Returns a url to a <filename>.css stylesheet. If versioned is true
     * elixir will be queried for the correct version of the stylesheet.
     *
     * @param $name
     * @param $versioned
     * @return string
     */
    function link_to_stylesheet($name, $versioned = false) {
        if (!$name) return '';

        $path = "css/{$name}.css";

        if (\Illuminate\Support\Facades\App::environment('production')) {
            $path = "css/{$name}.min.css";
        }

        $url = $versioned ? url(mix($path)) : url($path);

        return $url;
    }
}

if (! function_exists('link_to_script')) {
    /**
     * Returns a url to a <filename>.js script. If versioned is true
     * elixir will be queried for the correct version of the script.
     *
     * @param $name
     * @param $versioned
     * @return string
     */
    function link_to_script($name, $versioned = false) {
        if (!$name) return '';

        $path = "js/{$name}.js";

        if (\Illuminate\Support\Facades\App::environment('production')) {
            $path = "js/{$name}.min.js";
        }

        $url = $versioned ? url(mix($path)) : url($path);

        return $url;
    }
}

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

if (! function_exists('ui_format_money')) {
    function ui_format_money($value) {
        return number_format($value / 100,2,',','.');
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

if (!function_exists('procedure_label')) {
    function procedure_label($procedures) {
        $p = is_array($procedures) ? $procedures : [ $procedures ];
        if (count($p) == 1) {
            // 'singles'
            if ($p[0] == 'PT_OPEN') {
                return 'offenes Verfahren';
            }
            if ($p[0] == 'PT_COMPETITIVE_DIALOG') {
                return 'wettbewerblicher Dialog';
            }
            if ($p[0] == 'PT_COMPETITIVE_NEGOTIATION') {
                return 'Verhandlungsverfahren';
            }
            if ($p[0] == 'PT_INNOVATION_PARTNERSHIP') {
                return 'Innovationspartnerschaft';
            }
            if ($p[0] == 'PT_SPECIAL_SERVICE') {
                return 'Besonderer Dienstleistungsauftrag';
            }
            if ($p[0] == 'DPS') {
                return 'dynamisches Beschaffungssystem';
            }
            if ($p[0] == 'PT_DIRECT') {
                return 'Direktvergabe';
            }
        }
        if (count($p) == 2) {
            // 'doubles'
            if(in_array('PT_RESTRICTED',$p) && in_array('PT_WITH_PRIOR_NOTICE',$p)) {
                return 'nicht offenes Verfahren mit vorheriger Bekanntmachung';
            }
            if(in_array('PT_RESTRICTED',$p) && in_array('PT_WITHOUT_PRIOR_NOTICE',$p)) {
                return 'nicht offenes Verfahren ohne vorheriger Bekanntmachung';
            }
            if(in_array('PT_COMPETITIVE_NEGOTIATION',$p) && in_array('PT_WITH_PRIOR_NOTICE',$p)) {
                return 'Verhandlungsverfahren mit vorheriger Bekanntmachung';
            }
            if(in_array('PT_COMPETITIVE_NEGOTIATION',$p) && in_array('PT_WITHOUT_PRIOR_NOTICE',$p)) {
                return 'Verhandlungsverfahren ohne vorheriger Bekanntmachung';
            }
            if(in_array('PT_SPECIAL_SERVICE',$p) && in_array('PT_WITH_PRIOR_NOTICE',$p)) {
                return 'besonderer Dienstleistungsauftrag mit vorheriger Bekanntmachung';
            }
            if(in_array('PT_SPECIAL_SERVICE',$p) && in_array('PT_WITHOUT_PRIOR_NOTICE',$p)) {
                return 'besonderer Dienstleistungsauftrag ohne vorheriger Bekanntmachung';
            }
            if(in_array('PT_DIRECT',$p) && in_array('PT_WITH_PRIOR_NOTICE',$p)) {
                return 'Direktvergabe mit vorheriger Bekanntmachung';
            }
            if(in_array('PT_OPEN',$p) && in_array('PT_IDEA',$p)) {
                return 'offener Ideenwettbewerb';
            }
            if(in_array('PT_OPEN',$p) && in_array('PT_IMPLEMENTATION',$p)) {
                return 'offener Realisierungswettbewerb';
            }
            if(in_array('PT_RESTRICTED',$p) && in_array('PT_IDEA',$p)) {
                return 'nicht offener Ideenwettbewerb';
            }
            if(in_array('PT_RESTRICTED',$p) && in_array('PT_IMPLEMENTATION',$p)) {
                return 'nicht offener Realisierungswettbewerb';
            }
            if(in_array('PT_INVITED',$p) && in_array('PT_IDEA',$p)) {
                return 'geladener Ideenwettbewerb';
            }
            if(in_array('PT_INVITED',$p) && in_array('PT_IMPLEMENTATION',$p)) {
                return 'geladener Realisierungswettbewerb';
            }
        }

        // nothing ? should never happen
        return join(', ',$p);
    }
}