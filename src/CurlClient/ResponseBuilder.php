<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient\CurlClient;

use Psr\Http\Message\ResponseInterface;
use Serps\Exception;

class ResponseBuilder
{

    public static function buildResponse($raw, ResponseInterface $response, $headerSize)
    {

        $rawHeaders = substr($raw, 0, $headerSize);
        $content = (strlen($raw) === $headerSize) ? '' : substr($raw, $headerSize);


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

        $response = self::parseHeaders($headers, $response);

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $body->write($content);

        return $response;
    }

    /**
     * Parses headers from a raw header string
     * @param array $headers
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public static function parseHeaders(array $headers, ResponseInterface $response)
    {

        $statusLine = trim(array_shift($headers));
        $parts = explode(' ', $statusLine, 3);
        if (count($parts) < 2 || substr(strtolower($parts[0]), 0, 5) !== 'http/') {
            throw new \RuntimeException($statusLine . 'is not a valid HTTP status line');
        }
        $reasonPhrase = count($parts) > 2 ? $parts[2] : '';
        /** @var ResponseInterface $response */
        $response = $response
            ->withStatus((int) $parts[1], $reasonPhrase)
            ->withProtocolVersion(substr($parts[0], 5));

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
            if ($response->hasHeader($name)) {
                $response = $response->withAddedHeader($name, $value);
            } else {
                $response = $response->withHeader($name, $value);
            }
        }
        return $response;
    }
}
