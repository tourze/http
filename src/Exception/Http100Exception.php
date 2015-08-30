<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http100Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::CONTINUES;

}
