<?php

namespace App\Services;

use Crypt;
use Config;

/**
 * Methods for safe manipulation of urls
 */
class UrlService
{
    // "+", "/" and "=" are not url safe
    public static function base64UrlEncode($input)
    {
        return strtr(base64_encode(Crypt::encrypt($input)), '+/=', '._-');
    }

    public static function base64UrlDecode($input)
    {
        return Crypt::decrypt(base64_decode(strtr($input, '._-', '+/=')));
    }
}
