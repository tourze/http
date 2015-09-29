<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http509Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::BANDWIDTH_LIMIT_EXCEEDED;

}
