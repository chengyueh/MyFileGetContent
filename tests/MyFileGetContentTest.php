<?php

namespace Poyu;

class MyFileGetContentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * This function use FileProvider to test connection function
     * Because FileProvider works without the internet,
     * it can simply test parsing of connection function is correct or not
     */
    public function testConnection()
    {
        MyFileGetContent::setConnectionProvider('Poyu\FileProvider');

        $method = self::getPrivateFunction('connect');
        $genGetString = self::getPrivateFunction('genGetString');

        $testCases = [
            [
                'args' => ['php.net', '', 'http', 80],
                'goldendata' => 'phpnet80/index.html.gold'
            ],
            [
                'args' => ['php.net', 'manual/en/class.reflection.php', 'http', 80],
                'goldendata' => 'phpnet80/manual/en/class.reflection.php.gold'
            ],
            [
                'args' => ['phpunit.de', '', 'https', 443],
                'goldendata' => 'sslphpunitde443/index.html.gold'
            ],
            [
                'args' => ['phpunit.de', '', 'http', 80],
                'goldendata' => 'sslphpunitde443/index.html.gold'
            ]
        ];

        foreach ($testCases as $testCase) {
            $data = $method->invoke(
                null,
                $testCase['args'][0],
                $genGetString->invoke(
                    null,
                    $testCase['args'][1],
                    $testCase['args'][0]
                ),
                $testCase['args'][2],
                $testCase['args'][3]
            );
            $this->assertStringEqualsFile('tests/data/' . $testCase['goldendata'], $data);
        }
    }

    /**
     * This function test connect function's error control
     */
    public function testConnectionFail()
    {
        MyFileGetContent::setConnectionProvider('Poyu\FileProvider');

        $method = self::getPrivateFunction('connect');
        $genGetString = self::getPrivateFunction('genGetString');

        $testCases = [
            [
                'args' => ['nothishost.net', '', 'http', 80],
                'msg' => 'sock error'
            ],
            [
                'args' => ['a.b.c', '', 'ftp', 23],
                'msg' => 'Unknown protocol'
            ],
            [
                'args' => ['notfound.net', '', 'http', 80],
                'msg' => 'Http Error : 404'
            ]
        ];

        foreach ($testCases as $testCase) {
            $data = $method->invoke(
                null,
                $testCase['args'][0],
                $genGetString->invoke(
                    null,
                    $testCase['args'][1],
                    $testCase['args'][0]
                ),
                $testCase['args'][2],
                $testCase['args'][3]
            );
            $this->assertEquals(false, $data);
            $this->assertEquals($testCase['msg'], MyFileGetContent::lastError());
        }

    }

    /**
     * Test public get function
     *
     * @group integration
     */
    public function testGet()
    {
        MyFileGetContent::setConnectionProvider('Poyu\FileProvider');

        $testCases = [
            [
                'url' => 'http://php.net',
                'goldendata' => 'phpnet80/index.html.gold'
            ],
            [
                'url' => 'http://php.net/manual/en/class.reflection.php',
                'goldendata' => 'phpnet80/manual/en/class.reflection.php.gold'
            ],
            [
                'url' => 'https://phpunit.de/',
                'goldendata' => 'sslphpunitde443/index.html.gold'
            ],
            [
                'url' => 'http://phpunit.de',
                'goldendata' => 'sslphpunitde443/index.html.gold'
            ]
        ];

        foreach ($testCases as $testCase) {
            $data = MyFileGetContent::get($testCase['url']);
            $this->assertStringEqualsFile('tests/data/' . $testCase['goldendata'], $data);
        }

    }

    /**
     * This function test posy
     */
    public function testPost()
    {
        MyFileGetContent::setConnectionProvider('Poyu\FileProvider');

        $testCases = [
            [
                'url' => 'http://test.post/post.php',
                'postData' => [
                    'gg' => 10
                ],
                'goldendata' => 'testpost80/post.php.gold'
            ]
        ];

        foreach ($testCases as $testCase) {
            $data = MyFileGetContent::post($testCase['url'], $testCase['postData']);
            $arr = file('tests/data/' . $testCase['goldendata']);
            $this->assertStringEqualsFile('tests/data/' . $testCase['goldendata'], $data);
        }


    }

    /**
     * This function is to compare result of MyFileGetContent::get()
     * and wget using pupolar sites.
     *
     * @group net
     */
    public function testPopularWeb()
    {
        MyFileGetContent::setConnectionProvider('Poyu\SockProvider');
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
     *
     * @group net
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
     *
     * @group net
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
        $method = self::getPrivateFunction('parseUrl');

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
        $method = self::getPrivateFunction('parseUrl');

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
     * util function use reflection to get private function
     */
    private static function getPrivateFunction($funcName)
    {
        $class = new \ReflectionClass('Poyu\MyFileGetContent');
        $method = $class->getMethod($funcName);
        $method->setAccessible(true);
        return $method;
    }

}
