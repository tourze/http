<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http503Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::SERVICE_UNAVAILABLE;

}
