<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http407Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::PROXY_AUTHENTICATION_REQUIRED;

}
