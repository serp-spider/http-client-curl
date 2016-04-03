<?php
/**
 * @license see LICENSE
 */

namespace Serps\HttpClient\CurlClient;

use Serps\Exception;

class CurlException extends Exception
{
    protected $curlErr;
    protected $curlMessage;

    /**
     * CurlException constructor.
     * @param $curlErr
     * @param $curlMessage
     */
    public function __construct($curlErr, $curlMessage)
    {
        $this->curlErr = $curlErr;
        $this->curlMessage = $curlMessage;
        parent::__construct(
            'Curl was unable to process the request. Error code:' . $curlErr . '. Message : "' . $curlMessage . '""'
        );
    }

    /**
     * @return mixed
     */
    public function getCurlErrCode()
    {
        return $this->curlErr;
    }

    /**
     * @return mixed
     */
    public function getCurlErrMessage()
    {
        return $this->curlMessage;
    }
}
