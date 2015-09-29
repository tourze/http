<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http505Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::HTTP_VERSION_NOT_SUPPORTED;

}
