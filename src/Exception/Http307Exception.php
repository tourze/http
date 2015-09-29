<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http307Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::TEMPORARY_REDIRECT;

}
