<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http410Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::GONE;

}
