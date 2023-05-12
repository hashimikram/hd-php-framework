<?php
namespace HDFramework\src;

use League\ISO3166\ISO3166;

/**
 * Wrapper class for iso3166 library<br />
 * https://iso3166.thephpleague.com/using
 *
 * @author cornel
 * @package framework
 */
class HDISO3166
{

    private static $instance;

    public static function get()
    {
        if (is_null(self::$instance)) {
            self::$instance = new ISO3166();
        }
        return self::$instance;
    }
}