<?php
/**
 * @license see LICENSE
 */
namespace Serps\HttpClient;

use Psr\Http\Message\ResponseInterface;
use Serps\Core\Http\SearchEngineResponse;
use Serps\HttpClient\CurlClient;
use Zend\Diactoros\Request;

use Zend\Diactoros\Response;

/**
 * @covers Serps\HttpClient\CurlClient
 * @covers Serps\HttpClient\CurlClient\Curl
 * @covers Serps\HttpClient\CurlClient\ResponseBuilder
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
}
