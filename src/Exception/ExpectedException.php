<?php

namespace tourze\Http\Exception;

use tourze\Http\Response;

/**
 * Http异常，一般用于那些不需要显示错误信息的报错，如301、302
 *
 * @package tourze\Http\Exception
 */
abstract class ExpectedException extends HttpException
{

    /**
     * 设置header信息
     *
     * @param  mixed  $key
     * @param  string $value
     * @return mixed
     */
    public function headers($key = null, $value = null)
    {
        if (null === $value)
        {
            return $this->_response->headers($key);
        }

        $result = $this->_response->headers($key, $value);

        if ( ! $result instanceof Response)
        {
            return $result;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        $this->check();

        return $this->_response;
    }

}
