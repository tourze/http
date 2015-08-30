<?php

namespace tourze\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use tourze\Base\Helper\Arr;
use tourze\Base\Object;

/**
 * HTTP消息
 *
 * @package tourze\Http
 * @property string                 protocolVersion
 * @property array                  headers
 * @property string                 headerLines
 * @property string|StreamInterface body
 */
class Message extends Object implements MessageInterface
{

    /**
     * @var string 当前协议版本
     */
    protected $_protocolVersion = '1.1';

    /**
     * @var array HEADER信息数组
     */
    protected $_headers = [];

    /**
     * @var string|StreamInterface
     */
    protected $_body = '';

    /**
     * 返回当前协议版本，如1.1或1.0
     *
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->_protocolVersion;
    }

    /**
     * 设置协议版本
     *
     * @param string $protocolVersion
     */
    public function setProtocolVersion($protocolVersion)
    {
        $this->_protocolVersion = $protocolVersion;
    }

    /**
     * 返回一个指定协议版本的消息实例
     *
     * @param string $version HTTP版本
     * @return self
     */
    public function withProtocolVersion($version)
    {
        return new self([
            'protocolVersion' => $version
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * 一次性设置多个header
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->_headers = $headers;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name)
    {
        return isset($this->_headers[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        return isset($this->_headers[$name]) ? $this->_headers[$name] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        if ( ! isset($this->_headers[$name]))
        {
            return '';
        }

        return implode(', ', $this->_headers[$name]);
    }

    /**
     * 返回一个完整的header
     *
     * @return string
     */
    public function getHeaderLines()
    {
        $header = '';

        foreach ($this->getHeaders() as $key => $value)
        {
            // 格式化header的key
            $key = explode('-', $key);
            foreach ($key as $k => $v)
            {
                $key[$k] = ucfirst($v);
            }
            $key = implode('-', $key);

            if (is_array($value))
            {
                $header .= $key . ': ' . (implode(', ', $value));
            }
            else
            {
                $header .= $key . ': ' . $value;
            }
            $header .= "\r\n";
        }

        return $header . "\r\n";
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        if ( ! is_array($value))
        {
            $value = [$value];
        }

        $this->_headers[$name] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        if (isset($this->_headers[$name]))
        {
            // 如果是数组，那么合并
            if (is_array($value))
            {
                $this->_headers[$name] = Arr::merge($this->_headers[$name], $value);
            }
            // 否则直接新增
            else
            {
                $this->_headers[$name][] = $value;
            }
        }
        else
        {
            $this->withHeader($name, $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        if (isset($this->_headers[$name]))
        {
            unset($this->_headers[$name]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * 保存body
     *
     * @param StreamInterface|string $body
     * @return Message
     */
    public function setBody($body)
    {
        $this->_body = $body;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        return new self([
            'body' => $body
        ]);
    }
}
