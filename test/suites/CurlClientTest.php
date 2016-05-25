<?php
/**
 * @license see LICENSE
 */
namespace Serps\Test\HttpClient;

use Psr\Http\Message\ResponseInterface;
use Serps\Core\Cookie\ArrayCookieJar;
use Serps\Core\Cookie\Cookie;
use Serps\Core\Http\HttpClientInterface;
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
 */
class CurlClientTest extends HttpClientTestsCase
{
    public function getHttpClient()
    {
        return new CurlClient();
    }
}
