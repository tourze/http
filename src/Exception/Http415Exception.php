<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http415Exception extends HttpException
{

    /**
     * @var int
     */
    protected $_code = Http::UNSUPPORTED_MEDIA_TYPE;

}
