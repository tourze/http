<?php

namespace tourze\Http;

use tourze\Base\Base;
use tourze\Base\Component\Http as BaseHttp;
use tourze\Base\Helper\Url;
use tourze\Http\Exception\Http304Exception;

/**
 * 包含了一些http操作相关的基础信息和助手方法
 *
 * @package tourze\Http
 */
abstract class Http extends BaseHttp
{

    /**
     * @var  string  默认HTTP协议
     */
    public static $protocol = 'HTTP/1.1';

    /**
     * 跳转
     *
     * @param  string $uri  要跳转的URI
     * @param  int    $code 跳转时使用的http状态码
     */
    public static function redirect($uri = '', $code = 302)
    {
        if (false === strpos($uri, '://'))
        {
            $uri = Url::site($uri, true, ! empty(Base::$indexFile));
        }

        $response = new Response;
        $response->status = $code;

        $lastTime = gmdate("D, d M Y H:i:s", time()) . ' GMT+0800';
        $response->headers('Cache-Control', 'no-cache');
        $response->headers('Last Modified', $lastTime);
        $response->headers('Last Fetched', $lastTime);
        $response->headers('Expires', 'Thu Jan 01 1970 08:00:00 GMT+0800');
        $response->headers('Location', $uri);

        echo $response
            ->sendHeaders(true)
            ->body;

        Http::end();
    }

    /**
     * 检查请求缓存。如果缓存存在的话，那么返回“304 Not Modified”
     *
     * @param  Request  $request  Request
     * @param  Response $response Response
     * @param  string   $etag     Resource ETag
     * @throws Http304Exception
     * @return Response
     */
    public static function checkCache(Request $request, Response $response, $etag = null)
    {
        // 为空的话，生成新的etag
        if (null == $etag)
        {
            $etag = $response->generateEtag();
        }
        $response->headers('etag', $etag);

        // Add the Cache-Control header if it is not already set
        if ($response->headers('cache-control'))
        {
            $response->headers('cache-control', $response->headers('cache-control') . ', must-revalidate');
        }
        else
        {
            $response->headers('cache-control', 'must-revalidate');
        }

        // 检测是否有合适的etag
        if ($request->headers('if-none-match') && (string) $request->headers('if-none-match') === $etag)
        {
            // No need to send data again
            throw (new Http304Exception())->headers('etag', $etag);
        }

        return $response;
    }

    /**
     * 注意，不要直接使用php自带的header，而要使用这个方法
     *
     * @param string    $content
     * @param bool|true $replace
     * @param int       $httpResponseCode
     * @return bool
     */
    public static function header($content, $replace = true, $httpResponseCode = 0)
    {
        // 这样的写法其实很难看。。。
        if (class_exists('tourze\Server\Protocol\Http'))
        {
            return call_user_func_array(['tourze\Server\Protocol\Http', 'header'], [$content, $replace, $httpResponseCode]);
        }
        header($content, $replace, $httpResponseCode);
        return true;
    }

    /**
     * 作用等于php自带的[header_remove]
     *
     * @param string $name
     */
    public static function headerRemove($name)
    {
        // 这样的写法其实很难看。。。
        if (class_exists('tourze\Server\Protocol\Http'))
        {
            call_user_func_array(['tourze\Server\Protocol\Http', 'headerRemove'], [$name]);
            return;
        }
        header_remove($name);
    }

    /**
     * 检测当前是否发送了header信息
     *
     * @param string $file
     * @param int    $line
     * @return bool
     */
    public static function headersSent($file = null, $line = null)
    {
        return headers_sent($file, $line);
    }

    /**
     * 获取当前已经发送的header列表
     *
     * @return array
     */
    public static function headersList()
    {
        return headers_list();
    }

    /**
     * 替代php自带的[session_start]函数
     *
     * @return bool
     */
    public static function sessionStart()
    {
        // 这样的写法其实很难看。。。
        if (class_exists('tourze\Server\Protocol\Http'))
        {
            return call_user_func_array(['tourze\Server\Protocol\Http', 'sessionStart'], []);
        }

        return session_start();
    }

    /**
     * 替代php自带的[session_write_close]函数
     *
     * @return bool
     */
    public static function sessionWriteClose()
    {
        // 这样的写法其实很难看。。。
        if (class_exists('tourze\Server\Protocol\Http'))
        {
            return call_user_func_array(['tourze\Server\Protocol\Http', 'sessionWriteClose'], []);
        }

        session_write_close();
        return true;
    }

    /**
     * 解析请求，并读取其中的HEADER信息
     *
     * @return array
     */
    public static function requestHeaders()
    {
        // apache服务器
        if (function_exists('apache_request_headers'))
        {
            return apache_request_headers();
        }

        // PECL扩展加载了
        elseif (extension_loaded('http'))
        {
            return http_get_request_headers();
        }

        $headers = [];

        if ( ! empty($_SERVER['CONTENT_TYPE']))
        {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        if ( ! empty($_SERVER['CONTENT_LENGTH']))
        {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        foreach ($_SERVER as $key => $value)
        {
            // 跳过非HTTP开头的值
            if (strpos($key, 'HTTP_') !== 0)
            {
                continue;
            }

            $key = str_replace(['HTTP_', '_'], ['', '-'], $key);
            $key = strtolower($key);
            $headers[$key] = $value;
        }

        return $headers;
    }
}
