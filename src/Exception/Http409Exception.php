<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http409Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::CONFLICT;

}
