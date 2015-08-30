<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http101Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::SWITCHING_PROTOCOLS;

}
