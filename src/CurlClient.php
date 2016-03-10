<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Serps\Core\Cookie\CookieJarInterface;
use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\ProxyInterface;

use Serps\Core\Http\SearchEngineResponse;
use Serps\Core\UrlArchive;
use Serps\HttpClient\CurlClient\Curl;
use Serps\HttpClient\CurlClient\ResponseBuilder;
use Zend\Diactoros\Response;

class CurlClient implements HttpClientInterface
{

    /**
     * @var Curl
     */
    protected $client;


    public function __construct()
    {
        $this->client = new Curl();
    }

    /**
     * @inheritdoc
     */
    public function sendRequest(
        RequestInterface $request,
        ProxyInterface $proxy = null,
        CookieJarInterface $cookieJar = null
    ) {
        if ($proxy) {
            $proxyHost = $proxy->getIp();
            $proxyPort = $proxy->getPort();
            if ($user = $proxy->getUser()) {
                $proxyAuth = $user;
                if ($password = $proxy->getPassword()) {
                    $proxyAuth .= ':' . $proxyAuth;
                }
            } else {
                $proxyAuth = null;
            }
            $this->client->setOption(CURLOPT_PROXY, $proxyHost);
            $this->client->setOption(CURLOPT_PROXYPORT, $proxyPort);
            if ($proxyAuth) {
                $this->client->setOption(CURLOPT_PROXYUSERPWD, $proxyAuth);
            } else {
                $this->client->removeOption(CURLOPT_PROXYUSERPWD);
            }
        } else {
            $this->client->removeOption(CURLOPT_PROXY);
            $this->client->removeOption(CURLOPT_PROXYPORT);
            $this->client->removeOption(CURLOPT_PROXYUSERPWD);
        }

        $rawResponse = $this->client->request($request);
        $headerSize = $this->client->getInfo(CURLINFO_HEADER_SIZE);
        $effectiveUrl = UrlArchive::fromString($this->client->getInfo(CURLINFO_EFFECTIVE_URL));
        $initialUrl = UrlArchive::fromString((string)$request->getUri());

        return ResponseBuilder::buildResponse($rawResponse, $headerSize, $initialUrl, $effectiveUrl, $proxy);
    }
}
