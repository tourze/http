<?php

namespace tourze\Http\Exception;

use Exception;
use tourze\Base\Exception\BaseException;
use tourze\Http\Request;
use tourze\Http\Response;

/**
 * 基础的HTTP异常类
 *
 * @package tourze\Http\Exception
 */
class HttpException extends BaseException
{

    /**
     * 创建一个指定类型的HTTP异常类
     *
     * @param  int       $code      状态码
     * @param  string    $message   消息文本
     * @param  array     $variables 消息文本的翻译变量
     * @param  Exception $previous
     * @return HttpException
     */
    public static function factory($code, $message = null, array $variables = null, Exception $previous = null)
    {
        $class = 'tourze\Http\Exception\Http' . $code . 'Exception';

        return new $class($message, $variables, $previous);
    }

    /**
     * @var string
     */
    protected $_uri = '';

    /**
     * @var int HTTP状态码
     */
    protected $_code = 0;

    /**
     * @var Request 当前异常的请求实例
     */
    protected $_request;

    /**
     * @var Response Response对象
     */
    protected $_response;

    /**
     * {@inheritdoc}
     */
    public function __construct($message = "", array $variables = null, $code = 0, Exception $previous = null)
    {
        if ( ! $code)
        {
            $code = $this->_code;
        }
        parent::__construct($message, $variables, $code, $previous);

        // 准备一个response对象
        $this->_response = new Response;
        $this->_response->status = $this->_code;
    }

    /**
     * 保存当前请求对象
     *
     * @param  Request $request
     * @return HttpException
     */
    public function request(Request $request = null)
    {
        if (null === $request)
        {
            return $this->_request;
        }
        $this->_request = $request;
        return $this;
    }

    /**
     * 获取当前异常的输出
     */
    public function getResponse()
    {
        return BaseException::response($this);
    }

    /**
     * 异常抛出时跳转的地址
     *
     * @param string $uri
     * @return $this
     */
    public function location($uri = '')
    {
        $this->_uri = $uri;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->_code;
    }

    /**
     * @param int $code
     */
    public function setStatusCode($code)
    {
        $this->_code = $code;
    }
}
