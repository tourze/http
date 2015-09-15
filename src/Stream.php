<?php

namespace tourze\Http;

use Psr\Http\Message\StreamInterface;
use tourze\Base\Object;

/**
 * Stream，存放Request和Response的通用部分
 *
 * @property Message                message
 * @property string                 protocol
 * @property string                 charset
 * @property array                  cookies
 * @property StreamInterface|string body
 * @property int                    contentLength
 * @package tourze\Http
 */
abstract class Stream extends Object
{

    /**
     * @var string 当前编码
     */
    protected $_charset = 'utf-8';

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;
    }

    /**
     * @var Message 消息对象
     */
    protected $_message = null;

    /**
     * @param Message $message
     */
    public function setMessage($message)
    {
        $this->_message = $message;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * 读取响应内容
     *
     * @return StreamInterface|string
     */
    public function getBody()
    {
        return $this->message->body;
    }

    /**
     * 设置响应内容
     *
     * @param StreamInterface|string $body
     */
    public function setBody($body)
    {
        $this->message->body = $body;
    }

    /**
     * @inheritdoc
     */
    public function withBody(StreamInterface $body)
    {
        $this->message->withBody($body);
        return $this;
    }

    /**
     * 读取当前body的长度
     *
     * @return int
     */
    public function getContentLength()
    {
        return strlen($this->body);
    }

    /**
     * @var array cookie数据
     */
    protected $_cookies = [];

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->_cookies;
    }

    /**
     * @param array $cookies
     */
    public function setCookies($cookies)
    {
        $this->_cookies = $cookies;
    }

    /**
     * @var string 协议字符串
     */
    protected $_protocol = 'HTTP/1.1';

    /**
     * @return string
     */
    protected function getProtocol()
    {
        return $this->_protocol;
    }

    /**
     * @param string $protocol
     */
    protected function setProtocol($protocol)
    {
        $this->_protocol = strtoupper($protocol);
    }
}
