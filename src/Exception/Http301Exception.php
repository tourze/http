<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http301Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::MOVED_PERMANENTLY;

}
