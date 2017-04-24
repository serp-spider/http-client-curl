<?php
/**
 * @license see LICENSE
 */
namespace Serps\Test\HttpClient;

use Serps\Core\Cookie\Cookie;
use Serps\HttpClient\CurlClient\CookieFile;

/**
 * @covers Serps\HttpClient\CurlClient\CookieFile
 */
class CookieFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider curlSerializedCookiesDataProvider
     *
     * @param Cookie[] $cookies
     * @param string   $cookieStr
     */
    public function testCookieFileGenerate(array $cookies, $cookieStr)
    {
        $this->assertEquals($cookieStr, CookieFile::generate($cookies));
    }

    /**
     * @dataProvider curlSerializedCookiesDataProvider
     *
     * @param Cookie[] $cookies
     * @param string   $cookieStr
     */
    public function testCookieFileParse(array $cookies, $cookieStr)
    {
        $this->assertEquals($cookies, CookieFile::parse($cookieStr));
    }

    public function curlSerializedCookiesDataProvider()
    {
        return [
            [
                [
                    new Cookie('test1', 'value1', [
                        'domain'    => 'example.org',
                        'path'      => '/',
                        'secure'    => true,
                        'expires'   => 1468951248,
                        'http_only' => true
                    ])
                ],
                "#HttpOnly_example.org\tFALSE\t/\tTRUE\t1468951248\ttest1\tvalue1"
            ],
            [
                [
                    new Cookie('test2', urlencode("\t#$%^&!-+=/\\;\t"), [
                        'domain'    => '.a.b.c.d.example.org',
                        'path'      => '/a/b/c/d;e',
                        'secure'    => true,
                        'expires'   => 1468951248,
                        'http_only' => false
                    ])
                ],
                ".a.b.c.d.example.org\tTRUE\t/a/b/c/d;e\tTRUE\t1468951248\ttest2\t%09%23%24%25%5E%26%21-%2B%3D%2F%5C%3B%09"
            ],
        ];
    }
}
