<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http504Exception extends ExpectedException
{

    /**
     * @var int
     */
    protected $_code = Http::GATEWAY_TIMEOUT;

}
