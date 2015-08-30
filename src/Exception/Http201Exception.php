<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http201Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::CREATED;

}
