<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http205Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::RESET_CONTENT;

}
