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
use Serps\HttpClient\CurlClient\CookieFile;
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
     * Get the underlying curl wrapper. Useful to manually update configuration.
     * Be aware that some curl options will be forced be every requests.
     * @return Curl
     */
    public function getCurl()
    {
        return $this->client;
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
            $proxyHost = $proxy->getHost();
            $proxyPort = $proxy->getPort();
            $proxyType = $proxy->getType();
            switch ($proxyType) {
                case 'SOCKS4':
                    $this->client->setOption(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                    break;
                case 'SOCKS5':
                    $this->client->setOption(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                    break;
                default:
                    $this->client->setOption(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                    break;
            }
            if ($user = $proxy->getUser()) {
                $proxyAuth = $user;
                if ($password = $proxy->getPassword()) {
                    $proxyAuth .= ':' . $password;
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

        if ($cookieJar) {
            $cookieFileData = CookieFile::generate($cookieJar->all());

            $cookieFile = tempnam(sys_get_temp_dir(), 'serps_curlcookie');
            file_put_contents($cookieFile, $cookieFileData);

            $cookieJarFile = tempnam(sys_get_temp_dir(), 'serps_curlcookiejar');

            $this->client->setOption(CURLOPT_COOKIEFILE, $cookieFile);
            $this->client->setOption(CURLOPT_COOKIEJAR, $cookieJarFile);
        } else {
            $this->client->removeOption(CURLOPT_COOKIEFILE);
            $this->client->removeOption(CURLOPT_COOKIEJAR);
        }

        try {
            $rawResponse = $this->client->request($request);
            $headerSize = $this->client->getInfo(CURLINFO_HEADER_SIZE);
            $effectiveUrl = UrlArchive::fromString($this->client->getInfo(CURLINFO_EFFECTIVE_URL));
            $initialUrl = UrlArchive::fromString((string)$request->getUri());
            $this->client->close();

            if ($cookieJar) {
                $cookieJarData = file_get_contents($cookieJarFile);
                $cookies = CookieFile::parse($cookieJarData);
                foreach ($cookies as $cookie) {
                    $cookieJar->set($cookie);
                }
            }

            return ResponseBuilder::buildResponse(
                $rawResponse,
                $headerSize,
                $initialUrl,
                $effectiveUrl,
                $proxy
            );
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if ($cookieJar) {
                unlink($cookieFile);
                unlink($cookieJarFile);
            }
        }
    }
}
