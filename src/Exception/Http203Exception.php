<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http203Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::NON_AUTHORITATIVE_INFORMATION;

}
