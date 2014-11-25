<?php

namespace Poyu;

class MyFileGetContent
{
    private static $errMessage;
    private static $connectionProvider = 'Poyu\SockProvider';

    public static function get($url)
    {
        $arr = self::parseUrl($url);
        return self::connect($arr['host'], $arr['resource'], $arr['protocol'], $arr['port']);
    }

    public static function lastError()
    {
        return self::$errMessage;
    }

    public static function setConnectionProvider($connectionProvider)
    {
        self::$connectionProvider = $connectionProvider;
    }

    /**
     * This function parse the input url to host, resource and protocol,
     * and save in private variables.
     *
     * @param string String of url
     *
     * @return If input is invalid, return boolean false.
     *         Or, array contains protocol, host and resource.
     */
    private static function parseUrl($url)
    {
        //the valid url should be : http(s)://aaa.bbb.ccc/ddd/eee
        //so directly use / to explode:
        // http(s): / / aaa.bbb.ccc / ddd/eee
        //  0        1       2          3
        $splitArr = explode("/", $url, 4);

        if (3 > sizeof($splitArr)) {
            return false;
        }

        $returnArr = array();

        $returnArr['protocol'] = substr($splitArr[0], 0, -1);

        //host may contain port number
        if (false !== strpos($splitArr[2], ':')) {
            $explodeHost = explode(':', $splitArr[2]);
            $returnArr['host'] = $explodeHost[0];
            $returnArr['port'] = (int)$explodeHost[1];
        } else {
            $returnArr['host'] = $splitArr[2];

            if ('http' === $returnArr['protocol']) {
                $returnArr['port'] = 80;
            } elseif ('https' === $returnArr['protocol']) {
                $returnArr['port'] = 443;
            } else {
                $returnArr['port'] = -1;
            }
        }

        if (isset($splitArr[3])) {
            $returnArr['resource'] = $splitArr[3];
        } else {
            $returnArr['resource'] = '';
        }

        if (
            '' === $returnArr['host'] or
            '' === $returnArr['protocol'] or
            '' === $returnArr['port']
        ) {
            return false;
        }

        return $returnArr;
    }

    private static function connect($host, $resource, $protocol, $port)
    {
        if ('http' === $protocol) {
            $sock = new self::$connectionProvider($host, $port);
        } elseif ('https' === $protocol) {
             $sock = new self::$connectionProvider("ssl://" . $host, $port);
        } else {
             self::$errMessage = "Unknown protocol";
             return false;
        }

        if (false === $sock->connect()) {
            self::$errMessage = $sock->getError();
            return false;
        }

        $sock->write(
            "GET /$resource HTTP/1.1\r\n" .
            "Host: $host\r\n" .
            "Connection: Close\r\n\r\n"
        );

        $statusLine = $sock->getLine();
        $splitArr = explode(" ", $statusLine, 3);
        $returnCode = (int)$splitArr[1];

        $headers = array();
        while ($header = $sock->getLine()) {

            if ("\r\n" === $header) {
                break;
            }

            $splitArr = explode(":", $header, 2);
            $headers[$splitArr[0]] = trim($splitArr[1]);
        }

        //deal with HTTP code 302 and 301, need to redirect
        if (302 == $returnCode or 301 == $returnCode) {
            $arr = self::parseUrl($headers['Location']);
            return self::connect($arr['host'], $arr['resource'], $arr['protocol'], $arr['port']);
        } elseif (200 != $returnCode) {
            self::$errMessage = "Http Error : $returnCode";
            return false;
        }

        //only process when http status code is 200
        $contents = "";
        // if Transfer-Encoding is chunked, deal with it
        if (isset($headers['Transfer-Encoding']) and
            'chunked' === $headers['Transfer-Encoding']) {
            while ($data = $sock->getLine()) {
                $chunkSize = hexdec($data);

                while (0 < $chunkSize) {
                    $data = $sock->getLine();
                    $chunkSize -= strlen($data);
                    if (0 > $chunkSize) {
                        $data = substr($data, 0, -2);
                    }
                    $contents .= $data;
                }
            }
        // else, direct get
        } else {
            while ($data = $sock->getLine()) {
                $contents .= $data;
            }
        }

        return $contents;
    }
}

/*********************************************
 * Example usage
 *********************************************
 *
 * $result = MyFileGetContent::get('http://google.com');
 *
 * if ($result !== false) {
 *    echo $result;
 * }  else {
 *   echo MyFileGetContent::lastError() . "\n";
 * }
 *
 **********************************************/
