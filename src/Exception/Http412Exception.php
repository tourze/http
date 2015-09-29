<?php

namespace tourze\Http\Exception;

use tourze\Http\Component\Http;

class Http412Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::PRECONDITION_FAILED;

}
