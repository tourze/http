<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http400Exception extends HttpException
{

    /**
     * @var int Bad Request
     */
    protected $_code = Http::BAD_REQUEST;

}
