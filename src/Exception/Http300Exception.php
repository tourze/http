<?php

namespace tourze\Http\Exception;

use tourze\Http\Message;

class Http300Exception extends RedirectException
{

    /**
     * @var int
     */
    protected $_code = Message::MULTIPLE_CHOICES;

}
