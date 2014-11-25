<?php

namespace Poyu;

/**
 * This class is a mock for testing.
 * It reads local file and give data to client through getLine function
 * to simulate read data from internet.
 */
class FileProvider implements ConnectionProvider
{
    private $fp = null;
    private $root = 'tests/data';
    private $dirPath;
    private $fileName = 'index.html';

    /**
     * use url without special charactor as file path
     * ex. data pf http://11.22:80 will save in directory 'http112280'
     * and the full path will be $root/$dirPath
     */
    public function __construct($host, $port)
    {
        $removeArray = ['/', '.', ':'];
        $this->dirPath = str_replace($removeArray, '', $host . $port);
    }

    /**
     * file not exisit === host not exist
     */
    public function connect()
    {
        return file_exists($this->root . '/' . $this->dirPath);
    }

    public function getLine()
    {
        if (!$this->fp) {
            $this->fp = @fopen(
                $this->root . '/' . $this->dirPath . '/' . $this->fileName,
                'r'
            );

            if (!$this->fp) {
                return false;
            }
        }

        return fgets($this->fp);
    }

    /**
     * To get data from client.
     * Because we only carw about which resource client wants,
     * this function only handle first line.
     */
    public function write($data)
    {
        $lines = explode('\n', $data);
        $firstLine = $lines[0];
        $splitArr = explode(' ', $firstLine);
        //TODO? error handling
        if ('/' !== $splitArr[1]) {
            $this->fileName = substr($splitArr[1], 1);
        }
    }

    public function getError()
    {
        return 'sock error';
    }
}
