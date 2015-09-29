<?php

namespace tourze\Http\Exception;

use tourze\Base\Exception\BaseException;
use tourze\Http\Http;

class Http401Exception extends ExpectedException
{

    /**
     * @var int
     */
    protected $_code = Http::UNAUTHORIZED;

    /**
     * 指定基础认证的提示信息
     *
     * @param  string $challenge 基础认证返回信息，如`Basic realm="Control Panel"`
     * @return $this
     */
    public function authenticate($challenge = null)
    {
        if (null === $challenge)
        {
            return $this->headers('www-authenticate');
        }

        $this->headers('www-authenticate', $challenge);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        if (null === $this->headers('www-authenticate'))
        {
            throw new BaseException("A 'www-authenticate' header must be specified for a HTTP 401 Unauthorized");
        }

        return true;
    }

}
