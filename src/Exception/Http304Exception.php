<?php

namespace tourze\Http\Exception;

use tourze\Http\Http;

class Http304Exception extends ExpectedException
{

    /**
     * @var int
     */
    protected $_code = Http::NOT_MODIFIED;

}
