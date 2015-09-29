<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http414Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::REQUEST_URI_TOO_LONG;

}
