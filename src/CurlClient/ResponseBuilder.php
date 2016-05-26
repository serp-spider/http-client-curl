<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient\CurlClient;

use Psr\Http\Message\ResponseInterface;
use Serps\Core\Cookie\CookieJarInterface;
use Serps\Core\Cookie\SetCookieString;
use Serps\Core\Http\ProxyInterface;
use Serps\Core\Http\SearchEngineResponse;
use Serps\Core\UrlArchive;
use Serps\Exception;

class ResponseBuilder
{

    public static function buildResponse(
        $rawResponse,
        $headerSize,
        UrlArchive $initialUrl,
        UrlArchive $effectiveUrl,
        ProxyInterface $proxy = null
    ) {
    

        $rawHeaders = substr($rawResponse, 0, $headerSize);
        $content = (strlen($rawResponse) === $headerSize) ? '' : substr($rawResponse, $headerSize);

        // When a redirect response occurs, headers from all request will be appended with double \r\n
        // We only need the last set of headers
        $rawHeaders = explode("\r\n\r\n", $rawHeaders);
        $headers = null;
        for ($i=count($rawHeaders)-1; $i>=0; $i--) {
            if ($rawHeaders[$i]!=='') {
                $headers = explode("\r\n", $rawHeaders[$i]);
                break;
            }
        }
        if (!$headers) {
            throw new Exception('Invalid Response. No header was found');
        }


        $data = [
            'headers' => [],
            'status' => null,
            'status-text' => null
        ];
        self::parseHeaders($headers, $data);

        return new SearchEngineResponse(
            $data['headers'],
            $data['status'],
            $content,
            false,
            $initialUrl,
            $effectiveUrl,
            $proxy
        );
    }

    /**
     * Parses headers from a raw header string
     * @param array $headers
     * @return ResponseInterface
     */
    public static function parseHeaders(array $headers, &$data)
    {

        $statusLine = trim(array_shift($headers));
        $parts = explode(' ', $statusLine, 3);
        if (count($parts) < 2 || substr(strtolower($parts[0]), 0, 5) !== 'http/') {
            throw new \RuntimeException($statusLine . 'is not a valid HTTP status line');
        }

        $data['status'] = (int) $parts[1];
        $data['status-text'] =  count($parts) > 2 ? $parts[2] : '';

        // $protocolVersion = substr($parts[0], 5));

        foreach ($headers as $headerLine) {
            $headerLine = trim($headerLine);
            if ('' === $headerLine) {
                continue;
            }
            $parts = explode(':', $headerLine, 2);
            if (count($parts) !== 2) {
                throw new \RuntimeException($headerLine . ' is not a valid HTTP header line');
            }
            $name = trim(urldecode($parts[0]));
            $value = trim(urldecode($parts[1]));
            $data['headers'][$name][] = $value;
        }
    }
}
