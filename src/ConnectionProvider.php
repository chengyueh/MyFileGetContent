<?php

namespace Poyu;

interface ConnectionProvider
{
    /**
     * Connection Provider's constructor should be 2 params:
     *  $host : the host to connect.
     *  $port : port number to connect.
     */
    public function __construct($host, $port);

    /**
     * This function is to set up connection,
     * return true on success, false on fail.
     */
    public function connect();

    /**
     * This function get a line(split by \n) of message from connected host
     * and return as string. If there is no more data, return null
     */
    public function getLine();

    /**
     * This function write data to the connected host.
     */
    public function write($data);

    /**
     * This method return error message as string on error occurs.
     */
    public function getError();
}
