<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http408Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::REQUEST_TIMEOUT;

}
