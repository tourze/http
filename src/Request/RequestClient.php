<?php

namespace tourze\Http\Request;

use tourze\Base\Object;
use tourze\Http\Http;
use tourze\Http\Request;
use tourze\Http\Request\Exception\RequestException;
use tourze\Http\Response;
use tourze\Http\Request\Exception\ClientRecursionException;

/**
 * 请求的具体实现类，支持两种请求方式，一种是内部请求，一种是外部请求
 *
 * @property bool  follow
 * @property array followHeaders
 * @property bool  strictRedirect
 * @property array headerCallbacks
 * @property int   maxCallbackDepth
 * @property int   callbackDepth
 * @property array callbackParams
 * @package tourze\Http\Request
 */
abstract class RequestClient extends Object
{

    /**
     * @var bool 如果返回header有跳转，是否继续跟随跳转
     */
    protected $_follow = false;

    /**
     * @param bool $follow
     */
    public function setFollow($follow)
    {
        $this->_follow = $follow;
    }

    /**
     * @return bool
     */
    public function isFollow()
    {
        return $this->_follow;
    }

    /**
     * @var array 跳转之后，依然要保留的header信息
     */
    protected $_followHeaders = ['Authorization'];

    /**
     * @param array $followHeaders
     */
    public function setFollowHeaders($followHeaders)
    {
        $this->_followHeaders = $followHeaders;
    }

    /**
     * @return array
     */
    public function getFollowHeaders()
    {
        return $this->_followHeaders;
    }

    /**
     * @var bool 302重定向后依然使用之前的http方法？
     */
    protected $_strictRedirect = true;

    /**
     * @param bool $strictRedirect
     */
    public function setStrictRedirect($strictRedirect)
    {
        $this->_strictRedirect = $strictRedirect;
    }

    /**
     * @return bool
     */
    public function isStrictRedirect()
    {
        return $this->_strictRedirect;
    }

    /**
     * @var array 当请求带有指定的header信息时，自动执行回调函数
     */
    protected $_headerCallbacks = [
        'Location' => 'tourze\Http\Request\RequestClient::onHeaderLocation',
    ];

    /**
     * @param array $headerCallbacks
     */
    public function setHeaderCallbacks($headerCallbacks)
    {
        $this->_headerCallbacks = $headerCallbacks;
    }

    /**
     * @return array
     */
    public function getHeaderCallbacks()
    {
        return $this->_headerCallbacks;
    }

    /**
     * @var int header回调函数执行的最大次数
     */
    protected $_maxCallbackDepth = 5;

    /**
     * @param int $maxCallbackDepth
     */
    public function setMaxCallbackDepth($maxCallbackDepth)
    {
        $this->_maxCallbackDepth = $maxCallbackDepth;
    }

    /**
     * @return int
     */
    public function getMaxCallbackDepth()
    {
        return $this->_maxCallbackDepth;
    }

    /**
     * @var int 当前主请求的级别
     */
    protected $_callbackDepth = 1;

    /**
     * @param int $callbackDepth
     */
    public function setCallbackDepth($callbackDepth)
    {
        $this->_callbackDepth = $callbackDepth;
    }

    /**
     * @return int
     */
    public function getCallbackDepth()
    {
        return $this->_callbackDepth;
    }

    /**
     * @var array 回调参数
     */
    protected $_callbackParams = [];

    /**
     * @param array $callbackParams
     */
    public function setCallbackParams($callbackParams)
    {
        $this->_callbackParams = $callbackParams;
    }

    /**
     * @return array
     */
    public function getCallbackParams()
    {
        return $this->_callbackParams;
    }

    /**
     * 处理请求，根据路由中的信息，执行对应的控制器和动作
     *
     * @param  Request $request
     * @return Response
     * @throws ClientRecursionException
     * @throws RequestException
     */
    public function execute(Request $request)
    {
        // 防止一直循环
        if ($this->callbackDepth > $this->maxCallbackDepth)
        {
            throw new ClientRecursionException('Could not execute request to :uri - too many recursions after :depth requests', [
                ':uri'   => $request->uri,
                ':depth' => $this->callbackDepth - 1,
            ]);
        }

        $origResponse = $response = new Response([
            'protocol' => $request->protocol,
        ]);

        $response = $this->executeRequest($request, $response);

        foreach ($this->headerCallbacks as $header => $callback)
        {
            if ($response->headers($header))
            {
                $callbackResult = call_user_func($callback, $request, $response, $this);

                if ($callbackResult instanceof Request)
                {
                    $this->assignClientProperties($callbackResult->client);
                    $callbackResult
                        ->client
                        ->callbackDepth = $this->callbackDepth + 1;

                    $response = $callbackResult->execute();
                }
                elseif ($callbackResult instanceof Response)
                {
                    $response = $callbackResult;
                }

                if ($response !== $origResponse)
                {
                    break;
                }
            }
        }

        return $response;
    }

    /**
     * 执行请求，并返回接口
     *
     * @param  Request  $request 要处理的request实例
     * @param  Response $response
     * @return Response
     */
    abstract public function executeRequest(Request $request, Response $response);

    /**
     * 复制client的信息
     *
     * @param RequestClient $client
     */
    public function assignClientProperties(RequestClient $client)
    {
        $client->follow = $this->follow;
        $client->followHeaders = $this->followHeaders;
        $client->headerCallbacks = $this->headerCallbacks;
        $client->maxCallbackDepth = $this->maxCallbackDepth;
        $client->callbackParams = $this->callbackParams;
    }

    /**
     * 跳转状态码的处理
     *
     * @param Request       $request
     * @param Response      $response
     * @param RequestClient $client
     * @return null|Request
     */
    public static function onHeaderLocation(Request $request, Response $response, RequestClient $client)
    {
        if ($client->follow
            && in_array($response->status, [
                Http::CREATED,
                Http::MOVED_PERMANENTLY,
                Http::FOUND,
                Http::SEE_OTHER,
                Http::TEMPORARY_REDIRECT,
            ])
        )
        {
            switch ($response->status)
            {
                default:
                case Http::MOVED_PERMANENTLY:
                case Http::TEMPORARY_REDIRECT:
                    $followMethod = $request->method;
                    break;
                case Http::CREATED:
                case Http::SEE_OTHER:
                    $followMethod = Http::GET;
                    break;
                case Http::FOUND:
                    if ($client->strictRedirect)
                    {
                        $followMethod = $request->method;
                    }
                    else
                    {
                        $followMethod = Http::GET;
                    }
                    break;
            }

            $origHeaders = $request->headers();
            $followHeaders = array_intersect_assoc($origHeaders, array_fill_keys($client->followHeaders, true));

            $followRequest = Request::factory($response->headers('Location'));
            $followRequest->method = $followMethod;
            $followRequest->headers($followHeaders);


            if ($followMethod !== Http::GET)
            {
                $followRequest->body = $request->body;
            }

            return $followRequest;
        }

        return null;
    }
}
