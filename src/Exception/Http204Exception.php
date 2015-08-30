<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http204Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::NO_CONTENT;

}
