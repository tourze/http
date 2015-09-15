<?php

namespace tourze\Http;

use PHPUnit_Framework_TestCase;

/**
 * Class RequestTest
 *
 * @package tourze\Http
 */
class RequestTest extends PHPUnit_Framework_TestCase
{

    /**
     * 检测execute功能是否正常
     */
    public function testExecute()
    {
        $request = Request::factory('http://www.baidu.com');
        $response = $request->execute();

        $this->assertTrue(strpos($response->body, '百度') !== false);
    }
}
