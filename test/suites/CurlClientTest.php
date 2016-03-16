<?php
/**
 * @license see LICENSE
 */
namespace Serps\Test\HttpClient;

use Psr\Http\Message\ResponseInterface;
use Serps\Core\Cookie\ArrayCookieJar;
use Serps\Core\Cookie\Cookie;
use Serps\Core\Http\SearchEngineResponse;
use Serps\HttpClient\CookieFile;
use Serps\HttpClient\CurlClient;
use Zend\Diactoros\Request;

use Zend\Diactoros\Response;

/**
 * @covers Serps\HttpClient\CurlClient
 * @covers Serps\HttpClient\CurlClient\Curl
 * @covers Serps\HttpClient\CurlClient\ResponseBuilder
 * @covers Serps\HttpClient\CookieFile
 */
class CurlClientTest extends \PHPUnit_Framework_TestCase
{

    public function testGetRequest()
    {
        $client = new CurlClient();

        $request = new Request('http://httpbin.org/get', 'GET');
        $request = $request->withHeader('User-Agent', 'test-user-agent');

        $response = $client->sendRequest($request);
        $this->assertInstanceOf(SearchEngineResponse::class, $response);

        $responseData = json_decode($response->getPageContent(), true);
        $this->assertEquals(200, $response->getHttpResponseStatus());
        $this->assertEquals('test-user-agent', $responseData['headers']['User-Agent']);
        $this->assertEquals('http://httpbin.org/get', $response->getEffectiveUrl()->buildUrl());
    }

    public function testRedirectRequest()
    {
        $client = new CurlClient();

        $request = new Request('http://httpbin.org/redirect-to?url=get', 'GET');
        $request = $request->withHeader('User-Agent', 'test-user-agent');

        $response = $client->sendRequest($request);
        $this->assertInstanceOf(SearchEngineResponse::class, $response);

        $responseData = json_decode($response->getPageContent(), true);
        $this->assertEquals(200, $response->getHttpResponseStatus());
        $this->assertEquals('test-user-agent', $responseData['headers']['User-Agent']);
        $this->assertEquals('http://httpbin.org/get', $response->getEffectiveUrl()->buildUrl());
        $this->assertEquals('http://httpbin.org/redirect-to?url=get', $response->getInitialUrl()->buildUrl());
    }

    public function testCookieEmpty()
    {
        $client = new CurlClient();
        $request = new Request('http://httpbin.org/cookies', 'GET');

        $cookieJar = new ArrayCookieJar();

        $response = $client->sendRequest($request, null, $cookieJar);
        $responseData = json_decode($response->getPageContent(), true);

        $this->assertCount(0, $responseData['cookies']);
        $this->assertCount(0, $cookieJar->all());
    }

    public function testCookies()
    {
        $client = new CurlClient();
        $request = new Request('http://httpbin.org/cookies', 'GET');

        $cookieJar = new ArrayCookieJar();
        $cookieJar->set(new Cookie('foo', 'bar', ['domain' => '.httpbin.org']));
        $cookieJar->set(new Cookie('bar', 'baz', ['domain' => '.foo.org']));

        $response = $client->sendRequest($request, null, $cookieJar);
        $responseData = json_decode($response->getPageContent(), true);

        $this->assertCount(1, $responseData['cookies']);
        $this->assertEquals(['foo' => 'bar'], $responseData['cookies']);
        $this->assertCount(2, $cookieJar->all());
    }

    public function testSetCookies()
    {
        $client = new CurlClient();
        $request = new Request('http://httpbin.org/cookies/set?baz=bar', 'GET');

        $cookieJar = new ArrayCookieJar();

        $client->sendRequest($request, null, $cookieJar);

        $cookies = $cookieJar->all();

        $this->assertCount(1, $cookies);
        $this->assertEquals('baz', $cookies[0]->getName());
        $this->assertEquals('bar', $cookies[0]->getValue());
        $this->assertEquals('httpbin.org', $cookies[0]->getDomain());
    }
}
