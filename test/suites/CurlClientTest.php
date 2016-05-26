<?php
/**
 * @license see LICENSE
 */
namespace Serps\Test\HttpClient;

use Psr\Http\Message\ResponseInterface;
use Serps\Core\Cookie\ArrayCookieJar;
use Serps\Core\Cookie\Cookie;
use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\Proxy;
use Serps\Core\Http\SearchEngineResponse;
use Serps\HttpClient\CurlClient\CookieFile;
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
    public function getHttpClient()
    {
        return new CurlClient();
    }

    public function testCurlException()
    {

        $client = $this->getHttpClient();
        $request = new Request('http://httpbin.org/get', 'GET');

        try {
            $client->sendRequest($request, Proxy::createFromString('1111.0.0.0:50'));
            $this->fail('no exception thrown');
        } catch (CurlClient\CurlException $e) {
            $this->assertEquals(5, $e->getCurlErrCode());
            $this->assertNotEmpty($e->getCurlErrMessage());
        }

    }
}
