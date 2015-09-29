<?php

namespace tourze\Http\Exception;

use tourze\Base\Exception\BaseException;
use tourze\Http\Http;

class Http405Exception extends ExpectedException
{

    /**
     * @var int
     */
    protected $_code = Http::METHOD_NOT_ALLOWED;

    /**
     * 指定允许使用的方法列表
     *
     * @param  array $methods 允许的方法列表
     * @return $this
     */
    public function allowed($methods)
    {
        if (is_array($methods))
        {
            $methods = implode(', ', $methods);
        }

        $this->headers('allow', $methods);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        if (null === ($location = $this->headers('allow')))
        {
            throw new BaseException('A list of allowed methods must be specified');
        }

        return true;
    }

}
