<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http300Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Http::MULTIPLE_CHOICES;

}
