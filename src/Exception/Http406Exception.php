<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http406Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::NOT_ACCEPTABLE;

}
