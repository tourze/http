<?php

namespace tourze\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use tourze\Base\Object;
use tourze\Base\Exception\BaseException;
use tourze\Base\Helper\Arr;
use tourze\Base\Helper\Url;
use tourze\Base\Base;
use tourze\Http\Exception\HttpException;
use tourze\Http\Request\Client\ExternalClient;
use tourze\Http\Request\Client\InternalClient;
use tourze\Http\Request\Exception\RequestException;
use tourze\Http\Request\RequestClient;
use tourze\Route\Route;
use tourze\Base\Security\Valid;

/**
 * 请求处理类
 *
 * @property RequestClient client
 * @property Route         route
 * @property Message       message
 * @property array         routes
 * @property string        body
 * @property string        controller
 * @property string        action
 * @property string        directory
 * @property string        method
 * @property string        protocol
 * @property bool          ajax
 * @property bool          secure
 * @property bool          initial
 * @property string        uri
 * @property string        referrer
 * @property bool          external
 * @property string        requestedWith
 * @property array         params
 * @property array         get
 * @property array         post
 * @property array         cookies
 * @property array         persistRouteParams
 * @property int           contentLength
 * @package tourze\Http
 */
class Request extends Object implements RequestInterface
{

    /**
     * @var string 客户端UA
     */
    public static $userAgent = '';

    /**
     * @var string 客户端IP
     */
    public static $clientIp = '0.0.0.0';

    /**
     * @var string 可信任的代理服务器列表
     */
    public static $trustedProxies = [
        '127.0.0.1',
        'localhost',
    ];

    /**
     * @var static 系统主请求
     */
    public static $initialRequest;

    /**
     * @var static 当前正在处理的请求
     */
    public static $current;

    /**
     * 创建一个新的实例
     *
     *     $request = Request::factory($uri);
     *
     * If $cache parameter is set, the response for the request will attempt to be retrieved from the cache.
     *
     * @param   bool|string $uri            URI
     * @param   array       $clientParams   注入到client中的参数
     * @param   array       $injectedRoutes 注入到路由中的参数，一般用于测试
     * @return  Request|void
     * @throws  BaseException
     */
    public static function factory($uri = true, $clientParams = [], $injectedRoutes = [])
    {
        $request = new Request($uri, $clientParams, $injectedRoutes);

        return $request;
    }

    /**
     * 当前当前正在处理的请求
     *
     * @return Request
     */
    public static function current()
    {
        return Request::$current;
    }

    /**
     * 解析请求，查找路由
     *
     * @param  Request $request Request
     * @param  Route[] $routes  Route
     * @return array
     */
    public static function process(Request $request, $routes = null)
    {
        Base::getLog()->debug(__METHOD__ . ' process request and find the route');

        $routes = (empty($routes)) ? Route::all() : $routes;
        Base::getLog()->debug(__METHOD__ . ' get route list for process route', [
            'routes' => array_keys($routes)
        ]);

        $params = null;
        foreach ($routes as $name => $route)
        {
            Base::getLog()->debug(__METHOD__ . ' check route', [
                'name' => $name
            ]);
            /* @var $route Route */
            if ($params = $route->matches($request->uri, $request->method))
            {
                Base::getLog()->debug(__METHOD__ . ' matched route found', [
                    'name'   => $name,
                    'params' => $params,
                ]);
                return [
                    'params' => $params,
                    'route'  => $route,
                ];
            }
        }

        return null;
    }

    /**
     * 读取客户端IP地址
     *
     * @return string
     */
    public static function getClientIP()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && isset($_SERVER['REMOTE_ADDR'])
            && in_array($_SERVER['REMOTE_ADDR'], Request::$trustedProxies)
        )
        {
            // Format: "X-Forwarded-For: client1, proxy1, proxy2"
            $clientIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            return array_shift($clientIps);
        }
        elseif (isset($_SERVER['HTTP_CLIENT_IP'])
            && isset($_SERVER['REMOTE_ADDR'])
            && in_array($_SERVER['REMOTE_ADDR'], Request::$trustedProxies)
        )
        {
            $clientIps = explode(',', $_SERVER['HTTP_CLIENT_IP']);

            return array_shift($clientIps);
        }
        elseif (isset($_SERVER['REMOTE_ADDR']))
        {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '0.0.0.0';
    }

    /**
     * 根据URI创建一个新的请求对象
     *
     *     $request = new Request($uri);
     *
     * If $cache parameter is set, the response for the request will attempt to be retrieved from the cache.
     *
     * @param  string $uri            URI of the request
     * @param  array  $clientParams   Array of params to pass to the request client
     * @param  array  $injectedRoutes An array of routes to use, for testing
     * @throws RequestException
     */
    public function __construct($uri, $clientParams = [], $injectedRoutes = [])
    {
        $clientParams = is_array($clientParams) ? $clientParams : [];

        $this->message = new Message;
        $this->routes = $injectedRoutes;

        $splitUri = explode('?', $uri);
        $uri = array_shift($splitUri);

        if (null !== Request::$initialRequest)
        {
            if ($splitUri)
            {
                parse_str($splitUri[0], $this->_get);
            }
        }

        // 要区分内部链接和外部链接
        if (Valid::url($uri))
        {
            // 为其创建一个路由
            $this->route = new Route($uri);
            $this->uri = $uri;

            if (0 === strpos($uri, 'https://'))
            {
                $this->secure = true;
            }

            $this->external = true;
            $this->client = new ExternalClient($clientParams);

            Base::getLog()->debug(__METHOD__ . ' make an internal request', [
                'uri'    => $uri,
                'params' => $clientParams,
            ]);
        }
        else
        {
            $this->uri = trim($uri, '/');
            $this->client = new InternalClient($clientParams);

            Base::getLog()->debug(__METHOD__ . ' make an external request', [
                'uri'    => $uri,
                'params' => $clientParams,
            ]);
        }

        parent::__construct();
    }

    /**
     * @var RequestClient 请求处理驱动客户端
     */
    protected $_client;

    /**
     * @return RequestClient
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * @param RequestClient $client
     */
    public function setClient($client)
    {
        $this->_client = $client;
    }

    /**
     * @var string x-requested-with头，一般xmlhttprequest都有这个
     */
    protected $_requestedWith;

    /**
     * @return string
     */
    protected function getRequestedWith()
    {
        return $this->_requestedWith;
    }

    /**
     * @param string $requestWith
     */
    protected function setRequestedWith($requestWith)
    {
        $this->_requestedWith = $requestWith;
    }

    /**
     * @var string 请求方法，GET、POST或其他
     */
    protected $_method = 'GET';

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->_method = strtoupper($method);
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

    /**
     * @var bool 当前请求是否为安全连接
     */
    protected $_secure = false;

    /**
     * @return bool
     */
    protected function getSecure()
    {
        return $this->_secure;
    }

    /**
     * @param bool $secure
     */
    protected function setSecure($secure)
    {
        $this->_secure = (bool) $secure;
    }

    /**
     * @var string 来路URL
     */
    protected $_referrer;

    /**
     * @return string
     */
    protected function getReferrer()
    {
        return $this->_referrer;
    }

    /**
     * @param string $referrer
     */
    protected function setReferrer($referrer)
    {
        $this->_referrer = (string) $referrer;
    }

    /**
     * @var Route 当前请求匹配到的路由
     */
    protected $_route;

    /**
     * @return Route
     */
    protected function getRoute()
    {
        return $this->_route;
    }

    /**
     * @param Route $route
     */
    protected function setRoute(Route $route)
    {
        $this->_route = $route;
    }

    /**
     * @var Route[] 要在这些路由中查找请求，如果为空的话，那就直接用当前请求
     */
    protected $_routes;

    /**
     * @return Route[]
     */
    protected function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * @param Route[] $routes
     */
    protected function setRoutes($routes)
    {
        $this->_routes = $routes;
    }

    /**
     * @var string 控制器子目录
     */
    protected $_directory = '';

    /**
     * @return string
     */
    protected function getDirectory()
    {
        return $this->_directory;
    }

    /**
     * @param string $directory
     */
    protected function setDirectory($directory)
    {
        $this->_directory = (string) $directory;
    }

    /**
     * @var  string  当前请求要执行的控制器
     */
    protected $_controller;

    /**
     * @return string
     */
    protected function getController()
    {
        return $this->_controller;
    }

    /**
     * @param string $controller
     */
    protected function setController($controller)
    {
        $this->_controller = (string) $controller;
    }

    /**
     * @var  string  控制器要执行的动作
     */
    protected $_action;

    /**
     * @return string
     */
    protected function getAction()
    {
        return $this->_action;
    }

    /**
     * @param string $action
     */
    protected function setAction($action)
    {
        $this->_action = (string) $action;
    }

    /**
     * @var string 当前请求的URI
     */
    protected $_uri;

    /**
     * @return string
     */
    public function getUri()
    {
        return empty($this->_uri) ? '/' : $this->_uri;
    }

    /**
     * @param mixed $uri
     */
    protected function setUri($uri)
    {
        $this->_uri = $uri;
    }

    /**
     * @var bool 是否是外部请求
     */
    protected $_external = false;

    /**
     * @return bool
     */
    protected function getExternal()
    {
        return $this->_external;
    }

    /**
     * @param bool $external
     */
    protected function setExternal($external)
    {
        $this->_external = (bool) $external;
    }

    /**
     * @var null|Message
     */
    protected $_message = null;

    /**
     * @param null|Message $message
     * @return Request
     */
    public function setMessage($message)
    {
        $this->_message = $message;
        return $this;
    }

    /**
     * @return null|Message
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * 读取当前的body内容
     *
     * @return string
     */
    public function getBody()
    {
        return $this->message->body;
    }

    /**
     * 设置body内容
     *
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->message->body = $body;
        return $this;
    }

    /**
     * @var array 当前请求的路由参数
     */
    protected $_params = [];

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * @var array get参数
     */
    protected $_get = [];

    /**
     * @return array
     */
    public function getGet()
    {
        return $this->_get;
    }

    /**
     * @param array $get
     */
    public function setGet($get)
    {
        $this->_get = $get;
    }

    /**
     * @var array post参数
     */
    protected $_post = [];

    /**
     * @return array
     */
    public function getPost()
    {
        return $this->_post;
    }

    /**
     * @param array $post
     */
    public function setPost($post)
    {
        $this->_post = $post;
    }

    /**
     * @var array 要发送出来的cookie
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
     * @var bool 当前请求实例是否为初始实例
     */
    protected $_initial = false;

    /**
     * @return  boolean
     */
    public function isInitial()
    {
        return $this->_initial;
    }

    /**
     * @param bool $initial
     */
    public function setInitial($initial)
    {
        $this->_initial = $initial;
    }

    /**
     * @var array 要保留使用的路由参数
     */
    protected $_persistRouteParams = [
        'namespace',
        'directory',
        'controller',
        'action',
        'host',
    ];

    /**
     * @return array
     */
    public function getPersistRouteParams()
    {
        return $this->_persistRouteParams;
    }

    /**
     * @param array $persistRouteParams
     */
    public function setPersistRouteParams($persistRouteParams)
    {
        $this->_persistRouteParams = $persistRouteParams;
    }

    /**
     * 初始化
     */
    public function init()
    {
        // 如果当前运行环境是CLI，那么就会没有初始请求这个概念
        if ( ! self::$initialRequest)
        {
            self::$initialRequest = $this;
            $this->initial = true;
            $this->message->setHeaders(Http::requestHeaders());
        }
    }

    /**
     * 返回请求的输出
     *
     *     echo $request;
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * 创建一个当前请求的URL
     *
     * @param  mixed $protocol 协议字符串
     * @return string
     */
    public function url($protocol = null)
    {
        return Url::site($this->uri, $protocol);
    }

    /**
     * 获取路由参数
     *
     *     $id = $request->param('id');
     *
     * @param  string $key     参数名
     * @param  mixed  $default 默认返回值
     * @return mixed
     */
    public function param($key = null, $default = null)
    {
        // 一次性返回所有参数
        if (null === $key)
        {
            return $this->_params;
        }

        return Arr::get($this->_params, $key, $default);
    }

    /**
     * 执行请求
     *
     * @return \tourze\Http\Response
     * @throws \tourze\Http\Exception\HttpException
     * @throws \tourze\Http\Request\Exception\ClientRecursionException
     * @throws \tourze\Http\Request\Exception\RequestException
     */
    public function execute()
    {
        if ( ! $this->external)
        {
            Base::getLog()->debug(__METHOD__ . ' execute internal request');
            $processed = Request::process($this, $this->routes);

            if ($processed)
            {
                // 保存匹配到的路由
                $this->route = Arr::get($processed, 'route');
                $params = Arr::get($processed, 'params');

                // 是否为外部链接
                $this->external = $this->route->isExternal();

                // 控制器放在子目录中的情况
                if (isset($params['directory']))
                {
                    $this->directory = $params['directory'];
                }

                // 附加上命名空间
                if (isset($params['namespace']))
                {
                    $this->directory = $params['namespace'];
                }

                // 命名空间处理
                if ( ! $this->directory)
                {
                    $this->directory = Route::$defaultNamespace;
                }

                // 修正命名空间
                if (false === strpos($this->directory, '\\'))
                {
                    $this->directory = Route::$defaultNamespace . $this->directory . '\\';
                }

                // 保存控制器
                $this->controller = Arr::get($params, 'controller');

                // 保存动作
                $this->action = Arr::get($params, 'action', Route::$defaultAction);

                // 清理保留字段
                foreach ($this->persistRouteParams as $name)
                {
                    unset($params[$name]);
                }

                $this->_params = $params;

                Base::getLog()->debug(__METHOD__ . ' execute info', [
                    'directory'  => $this->directory,
                    'controller' => $this->controller,
                    'action'     => $this->action,
                    'params'     => $this->_params,
                ]);
            }
            else
            {
                Base::getLog()->debug(__METHOD__ . ' not route matched');
            }
        }

        if ( ! $this->route instanceof Route)
        {
            $e = HttpException::factory(Http::NOT_FOUND, 'Unable to find a route to match the URI: :uri', [
                ':uri' => $this->uri,
            ]);
            $e->request($this);

            throw $e;
        }

        if ( ! $this->client instanceof RequestClient)
        {
            throw new RequestException('Unable to execute :uri without a RequestClient', [
                ':uri' => $this->uri,
            ]);
        }

        return $this->client->execute($this);
    }

    /**
     * 当前请求是否是ajax请求的
     *
     * @return bool
     */
    public function isAjax()
    {
        return ('xmlhttprequest' === $this->requestedWith);
    }

    /**
     * 读取或者设置header信息
     *
     * @param  string|array $name  头部名或包含了头部名和数据的数组
     * @param  string       $value 值
     * @return mixed|$this
     */
    public function headers($name = null, $value = null)
    {
        if (is_array($name))
        {
            foreach ($name as $k => $v)
            {
                $this->message->withHeader($k, $v);
            }
            return $this;
        }

        if (null === $name)
        {
            return $this->message->getHeaders();
        }
        elseif (null === $value)
        {
            return $this->message->getHeaderLine($name);
        }

        $this->message->withHeader($name, $value);

        return $this;
    }

    /**
     * 设置或者读取cookie
     *
     * @param  mixed  $key   Cookie名，或一个cookie数组
     * @param  string $value 值
     * @return string
     * @return mixed
     */
    public function cookie($key = null, $value = null)
    {
        if (is_array($key))
        {
            $this->_cookies = $key;

            return $this;
        }
        elseif (null === $key)
        {
            return $this->_cookies;
        }
        elseif (null === $value)
        {
            return isset($this->_cookies[$key]) ? $this->_cookies[$key] : null;
        }

        $this->_cookies[$key] = (string) $value;
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
     * 渲染请求，保存：协议、头部、内容主体
     *
     * @return  string
     */
    public function render()
    {
        if ( ! $post = $this->post())
        {
            $body = $this->body;
        }
        else
        {
            $this->headers('content-type', 'application/x-www-form-urlencoded; charset=' . Base::$charset);
            $body = http_build_query($post, null, '&');
        }

        $this->headers('content-length', (string) $this->contentLength);

        if (Base::$expose)
        {
            $this->headers('user-agent', Base::version());
        }

        if ($this->_cookies)
        {
            $cookieString = [];

            // Parse each
            foreach ($this->_cookies as $key => $value)
            {
                $cookieString[] = $key . '=' . $value;
            }

            // 创建cookie字符串
            $this->headers('cookie', implode('; ', $cookieString));
        }

        $output = $this->method . ' ' . $this->uri . ' ' . $this->protocol . "\r\n";
        $output .= $this->message->headerLines;
        $output .= $body;

        return $output;
    }

    /**
     * 获取或者设置GET数据
     *
     * @param  mixed  $key   键名
     * @param  string $value 值
     * @return mixed|$this
     */
    public function query($key = null, $value = null)
    {
        if (is_array($key))
        {
            $this->_get = $key;
            return $this;
        }
        if (null === $key)
        {
            return $this->_get;
        }
        elseif (null === $value)
        {
            return Arr::path($this->_get, $key);
        }

        $this->_get[$key] = $value;
        return $this;
    }

    /**
     * 读取或者设置post数据
     *
     * @param  mixed  $key   键名
     * @param  string $value 值
     * @return mixed|$this
     */
    public function post($key = null, $value = null)
    {
        if (is_array($key))
        {
            $this->_post = $key;
            return $this;
        }
        if (null === $key)
        {
            return $this->_post;
        }
        elseif (null === $value)
        {
            return Arr::path($this->_post, $key);
        }

        $this->_post[$key] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->message->getProtocolVersion();
    }

    /**
     * @param string $version
     * @return $this
     */
    public function withProtocolVersion($version)
    {
        $this->message->withProtocolVersion($version);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->message->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name)
    {
        return $this->message->hasHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        return $this->message->getHeaderLine($name);
    }

    /**
     * @inheritdoc
     */
    public function withHeader($name, $value)
    {
        $this->message->withHeader($name, $value);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader($name, $value)
    {
        $this->message->withAddedHeader($name, $value);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader($name)
    {
        $this->message->withoutHeader($name);
        return $this;
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
     * @inheritdoc
     */
    public function getHeader($name)
    {
        return $this->headers($name);
    }

    /**
     * @inheritdoc
     */
    public function getRequestTarget()
    {
        return $this->uri;
    }

    /**
     * @inheritdoc
     */
    public function withRequestTarget($requestTarget)
    {
        $this->uri = $requestTarget;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $this->uri = (string) $uri;
        return $this;
    }
}
