<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http202Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::ACCEPTED;

}
