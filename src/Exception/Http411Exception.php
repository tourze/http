<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http411Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::LENGTH_REQUIRED;

}
