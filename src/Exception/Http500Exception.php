<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http500Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::INTERNAL_SERVER_ERROR;

}
