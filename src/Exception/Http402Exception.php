<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http402Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::PAYMENT_REQUIRED;

}
