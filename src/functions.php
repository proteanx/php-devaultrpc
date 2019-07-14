<?php

declare(strict_types=1);

namespace Protean\DeVault;

use Protean\DeVault\Exceptions\BadConfigurationException;
use Protean\DeVault\Exceptions\Handler as ExceptionHandler;

if (!function_exists('to_devault')) {
    /**
     * Converts from satoshi to devault.
     *
     * @param int $satoshi
     *
     * @return string
     */
    function to_devault(int $satoshi) : string
    {
        return bcdiv((string) $satoshi, (string) 1e8, 8);
    }
}

if (!function_exists('to_satoshi')) {
    /**
     * Converts from devault to satoshi.
     *
     * @param string|float $devault
     *
     * @return string
     */
    function to_satoshi($devault) : string
    {
        return bcmul(to_fixed((float) $devault, 8), (string) 1e8);
    }
}

if (!function_exists('to_ubtc')) {
    /**
     * Converts from devault to ubtc/bits.
     *
     * @param string|float $devault
     *
     * @return string
     */
    function to_ubtc($devault) : string
    {
        return bcmul(to_fixed((float) $devault, 8), (string) 1e6, 4);
    }
}

if (!function_exists('to_mbtc')) {
    /**
     * Converts from devault to mbtc.
     *
     * @param string|float $devault
     *
     * @return string
     */
    function to_mbtc($devault) : string
    {
        return bcmul(to_fixed((float) $devault, 8), (string) 1e3, 4);
    }
}

if (!function_exists('to_fixed')) {
    /**
     * Brings number to fixed precision without rounding.
     *
     * @param float $number
     * @param int   $precision
     *
     * @return string
     */
    function to_fixed(float $number, int $precision = 8) : string
    {
        $number = $number * pow(10, $precision);

        return bcdiv((string) $number, (string) pow(10, $precision), $precision);
    }
}

if (!function_exists('split_url')) {
    /**
     * Splits url into parts.
     *
     * @param string $url
     *
     * @return array
     */
    function split_url(string $url) : array
    {
        $allowed = ['scheme', 'host', 'port', 'user', 'pass'];

        $parts = (array) parse_url($url);
        $parts = array_intersect_key($parts, array_flip($allowed));

        if (!$parts || empty($parts)) {
            throw new BadConfigurationException(
                ['url' => $url],
                'Invalid url'
            );
        }

        return $parts;
    }
}

if (!function_exists('exception')) {
    /**
     * Gets exception handler instance.
     *
     * @return \Protean\DeVault\Exceptions\Handler
     */
    function exception() : ExceptionHandler
    {
        return ExceptionHandler::getInstance();
    }
}

set_exception_handler([ExceptionHandler::getInstance(), 'handle']);
