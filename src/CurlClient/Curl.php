<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient\CurlClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Request;
use Serps\HttpClient\CurlClient\ResponseBuilder;

/**
 * PSR-7 aware curl client
 */
class Curl
{

    protected $handler;
    protected $options = [];


    public function request(RequestInterface $request)
    {

        if (is_resource($this->handler)) {
            curl_reset($this->handler);
        } else {
            $this->handler = curl_init();
        }

        $options = $this->createRequestOptions($request);
        foreach ($options as $option => $value) {
            curl_setopt($this->handler, $option, $value);
        }

        $raw = curl_exec($this->handler);

        if (curl_errno($this->handler) > 0) {
            throw new CurlException(curl_errno($this->handler), curl_error($this->handler), $request);
        } else {
            return $raw;
        }
    }

    /**
     * Get infos from the curl transfert
     * @return array|mixed
     */
    public function getInfos()
    {
        if ($this->handler) {
            return curl_getinfo($this->handler);
        } else {
            return [];
        }
    }

    /**
     * Get the value of a given curl info
     * @param $info
     * @return mixed|null
     */
    public function getInfo($info)
    {
        if ($this->handler) {
            return curl_getinfo($this->handler, $info);
        } else {
            return null;
        }
    }

    /**
     * Get curl option. Typically they match to CURLOPT_* constants.
     * @param $option
     * @return mixed
     */
    public function getOption($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * Set a curl option. Typically they match to CURLOPT_* constants
     * @param $option
     * @param $value
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Removes a curl option that was previously set with @see Curl::setOption()
     * @param $option
     */
    public function removeOption($option)
    {
        if (isset($this->options[$option])) {
            unset($this->options[$option]);
        }
    }



    private function createRequestOptions(RequestInterface $request)
    {
        // DEFAULT
        $options = $this->options;
        $options[CURLOPT_HEADER] = true;
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_FOLLOWLOCATION] = true;
        $options[CURLOPT_HTTP_VERSION] = $this->getProtocolVersion($request->getProtocolVersion());
        $options[CURLOPT_URL] = (string) $request->getUri();

        // METHOD SPECIFIC
        if (in_array($request->getMethod(), ['OPTIONS', 'POST', 'PUT'], true)) {
            // cURL allows request body only for these methods.
            $body = (string) $request->getBody();
            if ('' !== $body) {
                $options[CURLOPT_POSTFIELDS] = $body;
            }
        }
        if ($request->getMethod() === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
        } elseif ($request->getMethod() === 'POST') {
            $options[CURLOPT_POST] = true;
        } elseif ($request->getMethod() !== 'GET') {
            // GET is a default method. Other methods should be specified explicitly.
            $options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
        }

        // HEADERS
        $options[CURLOPT_HTTPHEADER] = $this->createHeaders($request);

        // USER AGENT
        if ($request->hasHeader('User-Agent')) {
            $options[CURLOPT_USERAGENT] = implode(',', $request->getHeader('User-Agent'));
        }

        // AUTH
        if ($request->getUri()->getUserInfo()) {
            $options[CURLOPT_USERPWD] = $request->getUri()->getUserInfo();
        }
        return $options;
    }


    private function getProtocolVersion($requestVersion)
    {
        switch ($requestVersion) {
            case '1.0':
                return CURL_HTTP_VERSION_1_0;
            case '1.1':
                return CURL_HTTP_VERSION_1_1;
            case '2.0':
                if (defined('CURL_HTTP_VERSION_2_0')) {
                    return CURL_HTTP_VERSION_2_0;
                }
                throw new \UnexpectedValueException('libcurl 7.33 needed for HTTP 2.0 support');
        }
        return CURL_HTTP_VERSION_NONE;
    }


    private function createHeaders(RequestInterface $request)
    {
        $headerList = [];

        foreach ($request->getHeaders() as $header => $value) {
            $headerList[] = sprintf('%s: %s', $header, is_array($value) ? implode(', ', $value) : (string) $value);
        }

        return $headerList;
    }

    public function close()
    {
        if (is_resource($this->handler)) {
            curl_close($this->handler);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
