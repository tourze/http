<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http302Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::FOUND;

}
