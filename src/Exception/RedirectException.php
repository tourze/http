<?php

namespace tourze\Http\Exception;

use tourze\Base\Exception\BaseException;
use tourze\Base\Helper\Url;
use tourze\Base\Base;

/**
 * 跳转使用异常来实现跳转
 *
 * @package tourze\Http\Exception
 */
abstract class RedirectException extends ExpectedException
{

    /**
     * 跳转到指定URI
     *
     * @param  string $uri
     * @return $this
     */
    public function location($uri = null)
    {
        if (null === $uri)
        {
            return $this->headers('Location');
        }

        if (false === strpos($uri, '://'))
        {
            $uri = Url::site($uri, true, ! empty(Base::$indexFile));
        }

        $lastTime = gmdate("D, d M Y H:i:s", time()).' GMT+0800';
        $this->headers('Cache-Control', 'no-cache');
        $this->headers('Last Modified', $lastTime);
        $this->headers('Last Fetched', $lastTime);
        $this->headers('Expires', 'Thu Jan 01 1970 08:00:00 GMT+0800');
        $this->headers('Location', $uri);

        return $this;
    }

    /**
     * 校验是否有跳转地址了
     *
     * @throws BaseException
     * @return bool
     */
    public function check()
    {
        if (null === $this->headers('location'))
        {
            throw new BaseException("A 'location' must be specified for a redirect");
        }

        return true;
    }

}
