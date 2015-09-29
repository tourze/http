<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http417Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::EXPECTATION_FAILED;

}
