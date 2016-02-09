<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Serps\Core\Http\HttpClientInterface;
use Serps\Core\Http\ProxyInterface;

use Serps\HttpClient\CurlClient\Curl;
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
    public function sendRequest(RequestInterface $request, ProxyInterface $proxy = null)
    {
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

        $response = new Response('php://memory', 200, [
            'X-SERPS-PROXY' => $proxy ? (string)$proxy : ''
        ]);

        $response = $this->client->request($request, $response);

        $response = $response->withHeader('X-SERPS-EFFECTIVE-URL', $this->client->getInfo(CURLINFO_EFFECTIVE_URL));

        return $response;
    }
}
