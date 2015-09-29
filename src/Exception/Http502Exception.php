<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http502Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::BAD_GATEWAY;

}
