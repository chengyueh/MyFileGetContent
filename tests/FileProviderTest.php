<?php

namespace Poyu;

class FileProviderTest extends \PHPUnit_Framework_TestCase
{
    private $f;

    public function setUp()
    {
        $this->f = new FileProvider('http://12.34', 56);
    }

    public function testConnect()
    {
        $this->assertEquals(true, $this->f->connect());

        $f = new FileProvider('http://aa.bb.cc', 80);
        $this->assertEquals(false, $f->connect());
    }

    public function testGetLine()
    {
        $this->f->connect();
        $this->assertEquals("First line\n", $this->f->getLine());
        $this->assertEquals("Second line\n", $this->f->getLine());
        $this->assertEquals(null, $this->f->getLine());
    }

    public function testWrite()
    {
        $reflectionClass = new \ReflectionClass('Poyu\FileProvider');
        $property = $reflectionClass->getProperty('fileName');
        $property->setAccessible(true);

        $this->f->connect();
        $this->f->write(
            "GET / HTTP/1.1\r\n" .
            "Host: http://aa.bb\r\n" .
            "Connection: Close\r\n\r\n"
        );
        $this->assertEquals('index.html', $property->getValue($this->f));

        $this->f->write(
            "GET /test HTTP/1.1\r\n" .
            "Host: http://aa.bb\r\n" .
            "Connection: Close\r\n\r\n"
        );
        $this->assertEquals('test', $property->getValue($this->f));
    }

    public function testgetLineFailure()
    {
        $this->f->write(
            "GET /Nofile HTTP/1.1\r\n" .
            "Host: http://12.34\r\n" .
            "Connection: Close\r\n\r\n"
        );
        $this->assertEquals(false, $this->f->getLine());
    }
}
