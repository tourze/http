<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http403Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::FORBIDDEN;

}
