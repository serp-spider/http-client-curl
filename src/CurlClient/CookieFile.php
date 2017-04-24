<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient\CurlClient;

use Serps\Core\Cookie\Cookie;

class CookieFile
{

    const HTTP_ONLY_PREFIX = '#HttpOnly_';

    /**
     * Generate a string for curl cookie file
     *
     * The file generated will look like http://tutorialspots.com/curl-cookie-file-format-124.html
     *
     * @param Cookie[] $cookies
     * @return string the content to put in the curl cookie file
     */
    public static function generate(array $cookies)
    {

        $cookieFile = [];

        foreach ($cookies as $cookie) {
            $domain = $cookie->getDomain();
            $expire = $cookie->getExpires();
            $httpOnly = $cookie->getHttpOnly();
            $data = [
                ($httpOnly ? self::HTTP_ONLY_PREFIX : '' ) . $domain,
                $domain{0} == '.' ? 'TRUE' : 'FALSE',
                $cookie->getPath(),
                $cookie->getSecure() ? 'TRUE' : 'FALSE',
                $expire ? $expire : '0',
                $cookie->getName(),
                $cookie->getValue()
            ];
            $cookieFile[]= implode("\t", $data);
        }

        return implode(PHP_EOL, $cookieFile);
    }

    /**
     * @param $fileData
     * @return Cookie[]
     */
    public static function parse($fileData)
    {
        $cookies = [];

        $fileData = preg_split('/$\R?^/m', $fileData);
        foreach ($fileData as $cookieData) {
            $httpOnly = false;
            $prefixLength = strlen(self::HTTP_ONLY_PREFIX);
            if (strncmp($cookieData, self::HTTP_ONLY_PREFIX, $prefixLength) === 0) {
                $cookieData = substr($cookieData, $prefixLength);
                $httpOnly = true;
            }
            if (empty($cookieData) || $cookieData{0} == '#') {
                continue;
            }
            $cookieData = trim($cookieData);
            $cookieData = explode("\t", $cookieData);
            $cookies[] = new Cookie($cookieData[5], $cookieData[6], [
                'domain' => $cookieData[0],
                'path'   => $cookieData[2],
                'secure' => $cookieData[3] == 'TRUE',
                'expires' => $cookieData[4],
                'http_only' => $httpOnly,
            ]);
        }

        return $cookies;
    }
}
