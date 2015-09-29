<?php

namespace tourze\Http\Exception;

use tourze\Base\Exception\BaseException;
use tourze\Http\Http;

class Http305Exception extends ExpectedException
{

    /**
     * @var int
     */
    protected $_code = Http::USE_PROXY;

    /**
     * {@inheritdoc}
     */
    public function location($uri = null)
    {
        if (null === $uri)
        {
            return $this->headers('Location');
        }
        $this->headers('Location', $uri);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        if (null === ($location = $this->headers('location')))
        {
            throw new BaseException("A 'location' must be specified for a redirect");
        }

        if (false === strpos($location, '://'))
        {
            throw new BaseException('An absolute URI to the proxy server must be specified');
        }

        return true;
    }

}
