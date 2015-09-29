<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http303Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::SEE_OTHER;

}
