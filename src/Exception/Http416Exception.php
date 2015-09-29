<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http416Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::REQUESTED_RANGE_NOT_SATISFIABLE;

}
