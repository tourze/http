<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http206Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::PARTIAL_CONTENT;

}
