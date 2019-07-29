<?php

class PC {
    public static function debug($arg1, $args)
    {
		// do nothing
	}
}

if (!function_exists('d')) {

    function d($something)
    {
		// do nothing
	}
}

if (!function_exists('debug')) {

    function debug($a1, $a2 = '')
    {
		// do nothing
	}
}
