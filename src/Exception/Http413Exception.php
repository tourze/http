<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http413Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::REQUEST_ENTITY_TOO_LARGE;

}
