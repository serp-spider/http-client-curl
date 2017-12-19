<?php
/**
 * @license see LICENSE
 */
namespace Serps\Test\HttpClient;

use Serps\Core\Cookie\ArrayCookieJar;
use Serps\Core\Cookie\Cookie;
use Serps\Core\Http\Proxy;
use Serps\HttpClient\CurlClient;
use Zend\Diactoros\Request;

use Zend\Diactoros\Response;

/**
 * @covers Serps\HttpClient\CurlClient
 * @covers Serps\HttpClient\CurlClient\Curl
 * @covers Serps\HttpClient\CurlClient\ResponseBuilder
 * @covers Serps\HttpClient\CurlClient\CookieFile
 * @covers Serps\HttpClient\CurlClient\CurlException
 */
class CurlClientTest extends HttpClientTestsCase
{
    const FAKE_PROXY_HOST = '1111.0.0.0';
    const FAKE_PROXY_PORT = 50;
    const FAKE_PROXY_USER = 'user';
    const FAKE_PROXY_PASSWORD = 'password';
    const FAKE_PROXY_TYPE = 'HTTP';
    const FAKE_PROXY_TYPE_CURL = CURLPROXY_HTTP;

    public function getHttpClient()
    {
        return new CurlClient();
    }

    public function getRequest()
    {
        return new Request('http://httpbin.org/get', 'GET');
    }

    public function getFakeProxy()
    {
        return new Proxy(self::FAKE_PROXY_HOST, self::FAKE_PROXY_PORT, self::FAKE_PROXY_USER, self::FAKE_PROXY_PASSWORD, self::FAKE_PROXY_TYPE);
    }

    /**
     * This test a bug that makes curl ignore last cookie if no new line is inserted at the end of cookie file
     */
    public function testCookieAlone()
    {
        $client = $this->getHttpClient();
        $request = new Request('http://httpbin.org/cookies', 'GET');
        $cookieJar = new ArrayCookieJar();
        $cookieJar->set(new Cookie('bar', 'baz', ['domain' => '.httpbin.org']));
        $response = $client->sendRequest($request, null, $cookieJar);
        $responseData = json_decode($response->getPageContent(), true);
        $this->assertCount(1, $responseData['cookies']);
        $this->assertEquals(['bar' => 'baz'], $responseData['cookies']);
    }

    public function testCurlException()
    {
        $client = $this->getHttpClient();

        try {
            $client->sendRequest($this->getRequest(), $this->getFakeProxy());
            $this->fail('no exception thrown');
        } catch (CurlClient\CurlException $e) {
            $this->assertEquals(5, $e->getCurlErrCode());
            $this->assertNotEmpty($e->getCurlErrMessage());
        }
    }

    public function testGetCurl()
    {
        $this->assertInstanceOf(CurlClient\Curl::class, $this->getHttpClient()->getCurl());
    }

    public function testProxyConfiguration()
    {
        $client = $this->getHttpClient();

        try {
            $client->sendRequest($this->getRequest(), $this->getFakeProxy());
            $this->fail('no exception thrown');
        } catch (CurlClient\CurlException $e) {
            $this->assertEquals(self::FAKE_PROXY_HOST, $client->getCurl()->getOption(CURLOPT_PROXY));
            $this->assertEquals(self::FAKE_PROXY_PORT, $client->getCurl()->getOption(CURLOPT_PROXYPORT));
            $this->assertEquals(self::FAKE_PROXY_USER . ':' . self::FAKE_PROXY_PASSWORD, $client->getCurl()->getOption(CURLOPT_PROXYUSERPWD));
            $this->assertEquals(self::FAKE_PROXY_TYPE_CURL, $client->getCurl()->getOption(CURLOPT_PROXYTYPE));
        }
    }
}
