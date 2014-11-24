<?php

use poyu\MyFileGetContent;

class MyFileGetContentTest extends PHPUnit_Framework_TestCase
{
    /**
     * This function is to compare result of MyFileGetContent::get()
     * and wget using pupolar sites.
     */
    public function testPopularWeb()
    {
        $sites = [
            'http://php.net',
            'http://cs.nctu.edu.tw',
            'http://linux.vbird.org/',
            'https://phpunit.de/'
        ];

        foreach ($sites as $site) {
            $data = MyFileGetContent::get($site);
            file_put_contents('result', $data);
            exec('wget -O goldendata ' . $site);
            $resultString = exec('diff goldendata result');
            exec('rm -f goldendata result');
            $this->assertEquals("", $resultString);
        }
    }

    /**
     * This function is to connect to invalid site,
     * test if error message is OK.
     */
    public function testHttpError()
    {
        $sites = [
            'http://cs.nctu.edu.tw/GGGGGG',
            'http://cs.nctu.edu.tw/~poyu/'
        ];

        foreach ($sites as $site) {
            $result = MyFileGetContent::get($site);
            $this->assertEquals(false, $result);
            $this->assertRegExp('/Http Error : [0-9]{3}/', MyFileGetContent::lastError());
        }

    }

    /**
     * This function tries to connect to invalid host,
     * test if error message is OK
     */
    public function testSockError()
    {
        $sites = [
            'http://gg.gg.gg.gg',
            'http://abcdefg/sdfsdf'
        ];

        foreach ($sites as $site) {
            $result = MyFileGetContent::get($site);
            $this->assertEquals(false, $result);
            $this->assertEquals(
                'php_network_getaddresses: getaddrinfo failed: hostname nor servname provided, or not known',
                MyFileGetContent::lastError()
            );
        }
    }

    /**
     * This function test MyFileGetContent::parseUrl
     */
    public function testParseUrl()
    {
        $method = self::getParseUrlFunction();

        $dataSets = [
            [
                'url' => 'http://a.b.c',
                'result' => [
                    'protocol' => 'http',
                    'host' => 'a.b.c',
                    'port' => '80',
                    'resource' => ''
                ]
            ],
            [
                'url' => 'http://a.b.c/',
                'result' => [
                    'protocol' => 'http',
                    'host' => 'a.b.c',
                    'port' => '80',
                    'resource' => ''
                ]
            ],
            [
                'url' => 'http://a.b.c/d/e',
                'result' => [
                    'protocol' => 'http',
                    'host' => 'a.b.c',
                    'port' => '80',
                    'resource' => 'd/e'
                ]
            ],
            [
                'url' => 'https://a.b.c',
                'result' => [
                    'protocol' => 'https',
                    'host' => 'a.b.c',
                    'port' => '443',
                    'resource' => ''
                ]
            ],
            [
                'url' => 'http://a.b.c:5566',
                'result' => [
                    'protocol' => 'http',
                    'host' => 'a.b.c',
                    'port' => '5566',
                    'resource' => ''
                ]
            ],
            [
                'url' => 'http://a.b.c:5566/d/e',
                'result' => [
                    'protocol' => 'http',
                    'host' => 'a.b.c',
                    'port' => '5566',
                    'resource' => 'd/e'
                ]
            ]
        ];

        foreach ($dataSets as $dataSet) {
            $result = $method->invoke(null, $dataSet['url']);
            $this->assertEquals($dataSet['result'], $result);
        }
    }

    /**
     * This function test MyFileGetrContent::parseUrl's
     * error handling
     */
    public function testParseUrlError()
    {
        $method = self::getParseUrlFunction();

        $urls = [
            'a.b.c',
            '',
            '////////'
        ];
        foreach ($urls as $url) {
            $result = $method->invoke(null, $url);
            $this->assertEquals(false, $result);
        }
    }

    /**
     * This function uses reflection to get private function
     * MyFileGetContent::parseUrl()
     */
    private static function getParseUrlFunction()
    {
        $class = new ReflectionClass('poyu\MyFileGetContent');
        $method = $class->getMethod('parseUrl');
        $method->setAccessible(true);
        return $method;
    }
}
