<?php

namespace Poyu;

class SockProvider implements ConnectionProvider
{
    private $host;
    private $port;
    private $errorMessage;

    private $fp;

    private static $timeout = 30;

    /**
     * Constructor
     */
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * This function connect to assigned resource.
     * If data fetch is OK, return true.
     * If any error occurs, return false.
     */
    public function connect()
    {
        $this->fp = @fsockopen(
            $this->host,
            $this->port,
            $errno,
            $this->errorMessage,
            self::$timeout
        );

        if (!$this->fp) {
            return false;
        }

        return true;
    }

    /**
     * This function provide user to get a line of
     * content we get in connect funtion.
     * If there is no more data, return null.
     */
    public function getLine()
    {
        if (feof($this->fp)) {
            return null;
        }
        return fgets($this->fp);
    }

    /**
     * This function provide user to write data to resource
     */
    public function write($data)
    {
        fwrite($this->fp, $data);
    }

    /**
     * This function give error message to user on failure
     */
    public function getError()
    {
        return $this->errorMessage;
    }

}
