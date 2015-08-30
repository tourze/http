<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http200Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::OK;

}
