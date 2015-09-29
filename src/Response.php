<?php

namespace tourze\Http;

use Exception;
use Psr\Http\Message\ResponseInterface;
use tourze\Base\Helper\Mime;
use tourze\Base\Exception\BaseException;
use tourze\Base\Helper\Arr;
use tourze\Base\Helper\Cookie;
use tourze\Base\Base;
use tourze\Http\Request\Exception\RequestException;

/**
 * 请求响应对象
 *
 * @property int status
 * @package tourze\Http
 */
class Response extends Stream implements ResponseInterface
{

    /**
     * @var self 保存当前的response
     */
    public static $current = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->message = new Message;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getHeader($name)
    {
        return $this->message->getHeader($name);
    }

    /**
     * @var int 返回的HTTP状态码
     */
    protected $_status = 200;

    /**
     * @return int
     */
    protected function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param int $status
     * @throws BaseException
     */
    protected function setStatus($status)
    {
        if (array_key_exists($status, Http::$text))
        {
            $this->_status = (int) $status;
        }
        else
        {
            throw new BaseException(__METHOD__ . ' unknown status value : :value', [':value' => $status]);
        }
    }

    /**
     * 输出网页内容
     *
     * @return string
     */
    public function __toString()
    {
        return $this->body;
    }

    /**
     * 输出header信息
     *
     *       // 获取指定头信息
     *       $accept = $response->headers('Content-Type');
     *
     *       // 设置头信息
     *       $response->headers('Content-Type', 'text/html');
     *
     *       // 获取所有头信息
     *       $headers = $response->headers();
     *
     *       // 一次设置多个头信息
     *       $response->headers(['Content-Type' => 'text/html', 'Cache-Control' => 'no-cache']);
     *
     * @param mixed  $name
     * @param string $value
     * @return mixed
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
     *     // 读取cookie
     *     $cookies = $response->cookie();
     *
     *     // 写cookie
     *     $response->cookie('session', [
     *          'value' => $value,
     *          'expiration' => 12352234
     *     ]);
     *
     * @param  mixed  $key   cookie名
     * @param  string $value cookie值
     * @return $this|mixed
     */
    public function cookie($key = null, $value = null)
    {
        if (null === $key)
        {
            return $this->_cookies;
        }
        elseif ( ! is_array($key) && ! $value)
        {
            return Arr::get($this->_cookies, $key);
        }

        if (is_array($key))
        {
            foreach ($key as $k => $v)
            {
                $this->cookie($k, $v);
            }
        }
        else
        {
            if ( ! is_array($value))
            {
                $value = [
                    'value'      => $value,
                    'expiration' => Cookie::$expiration,
                ];
            }
            elseif ( ! isset($value['expiration']))
            {
                $value['expiration'] = Cookie::$expiration;
            }

            $this->_cookies[(string) $key] = $value;
        }

        return $this;
    }

    /**
     * 删除指定cookie
     *
     * @param  string $name
     * @return $this
     */
    public function deleteCookie($name)
    {
        unset($this->_cookies[$name]);
        return $this;
    }

    /**
     * 清空所有cookie
     *
     * @return $this
     */
    public function deleteCookies()
    {
        $this->cookies = [];
        return $this;
    }

    /**
     * 发送当前保存的header信息
     *
     * @param  bool     $replace  替换已经存在的header
     * @param  callback $callback 是否自定义处理函数
     * @return $this
     */
    public function sendHeaders($replace = false, $callback = null)
    {
        $protocol = $this->protocol;
        $status = $this->status;
        $cookies = $this->cookie();
        $headers = $this->headers();

        Base::getLog()->debug(__METHOD__ . ' begin to send header', [
            'protocol' => $protocol,
            'status'   => $status,
            'header'   => $headers,
            'cookie'   => $cookies,
        ]);

        $renderHeaders = [$protocol . ' ' . $status . ' ' . Arr::get(Http::$text, $status)];

        foreach ($headers as $header => $value)
        {
            if (is_array($value))
            {
                $value = implode(', ', $value);
            }

            // 格式化header的key
            $header = explode('-', $header);
            foreach ($header as $k => $v)
            {
                $header[$k] = ucfirst($v);
            }
            $header = implode('-', $header);

            $renderHeaders[] = $header . ': ' . $value;
        }

        if (isset($headers['content-type']) || isset($headers['Content-Type']))
        {
            // 已经有content-type了，这里就不做处理了
        }
        else
        {
            // 默认content-type
            $renderHeaders[] = 'Content-Type: ' . Base::$contentType . '; charset=' . $this->charset;
        }

        if (Base::$expose && ! isset($headers['x-powered-by']))
        {
            $renderHeaders[] = 'X-Powered-By: ' . Base::version();
        }

        if ($cookies)
        {
            $renderHeaders['Set-Cookie'] = $cookies;
        }

        if (is_callable($callback))
        {
            // Use the callback method to set header
            return call_user_func($callback, $this, $renderHeaders, $replace);
        }
        else
        {
            $this->_sendHeadersToPhp($renderHeaders, $replace);

            return $this;
        }
    }

    /**
     * 发送header信息
     *
     * @param  array $headers
     * @param  bool  $replace
     * @return $this
     */
    protected function _sendHeadersToPhp(array $headers, $replace)
    {
        if (Base::getHttp()->headersSent())
        {
            return $this;
        }

        foreach ($headers as $key => $line)
        {
            Base::getLog()->debug(__METHOD__ . ' send headers to php', [
                'key'   => $key,
                'value' => $line,
            ]);
            if ($key == 'Set-Cookie' && is_array($line))
            {
                Base::getLog()->debug(__METHOD__ . ' set cookie headers', $line);

                foreach ($line as $name => $value)
                {
                    Cookie::set($name, $value['value'], $value['expiration']);
                }
                continue;
            }

            Base::getHttp()->header($line, $replace);
        }

        return $this;
    }

    /**
     * 通过HTTP发送文件，达到下载文件的效果
     *
     *      // 下载一个已经存在的文件
     *      $response->sendFile('media/packages/package.zip');
     *
     *      // 将返回内容当做是下载处理：
     *      $response->body = $content;
     *      $response->sendFile(true, $filename);
     *
     * @param  string $filename 文件名、文件路径，如果设置为`true`则返回当前的response内容
     * @param  string $download 下载文件名，默认为当前要处理的文件名
     * @param  array  $options  其他选项
     * @throws BaseException
     */
    public function sendFile($filename, $download = null, array $options = null)
    {
        // 强制指定了mime
        if ( ! empty($options['mime_type']))
        {
            $mime = $options['mime_type'];
        }

        // 返回当前的响应内容，作为本次下载
        if (true === $filename)
        {
            if (empty($download))
            {
                throw new BaseException('Download name must be provided for streaming files');
            }

            $options['delete'] = false;

            if ( ! isset($mime))
            {
                // 根据扩展名，获取指定的mime类型
                $mime = Mime::getMimeFromExtension(strtolower(pathinfo($download, PATHINFO_EXTENSION)));
            }

            // 将响应内容保存到临时文件
            $fileData = (string) $this->message->body;
            $size = strlen($fileData);
            $file = tmpfile();
            fwrite($file, $fileData);

            unset($fileData);
        }
        else
        {
            $filename = realpath($filename);

            if (empty($download))
            {
                $download = pathinfo($filename, PATHINFO_BASENAME);
            }

            $size = filesize($filename);

            if ( ! isset($mime))
            {
                $mime = Mime::getMimeFromExtension(pathinfo($download, PATHINFO_EXTENSION));
            }

            $file = fopen($filename, 'rb');
        }

        if ( ! is_resource($file))
        {
            throw new BaseException('Could not read file to send: :file', [
                ':file' => $download,
            ]);
        }

        // inline和attachment的区别，主要在于浏览器遇到inline类型的下载时，会尝试直接在浏览器打开，而attachment则不会
        $disposition = Arr::get($options, 'inline') ? 'inline' : 'attachment';

        $temp = $this->_calculateByteRange($size);
        $start = array_shift($temp);
        $end = array_shift($temp);

        if ( ! empty($options['resumable']))
        {
            if ($start > 0 || $end < ($size - 1))
            {
                // Partial Content
                $this->status = 206;
            }

            $this->headers('content-range', 'bytes ' . $start . '-' . $end . '/' . $size);
            $this->headers('accept-ranges', 'bytes');
        }

        $this->headers('content-disposition', $disposition . '; filename="' . $download . '"');
        $this->headers('content-type', $mime);
        $this->headers('content-length', (string) (($end - $start) + 1));

        $this->sendHeaders();

        // @notice 此方法在cli环境下可能无效

        while (ob_get_level())
        {
            ob_end_flush();
        }

        ignore_user_abort(true);

        $prevTimeLimit = ini_get('max_execution_time');
        @set_time_limit(0);

        // 16K
        $block = 1024 * 16;

        fseek($file, $start);

        while ( ! feof($file) && ($pos = ftell($file)) <= $end)
        {
            if (connection_aborted())
            {
                break;
            }

            if ($pos + $block > $end)
            {
                $block = $end - $pos + 1;
            }
            echo fread($file, $block);
            flush();
        }

        fclose($file);

        if ( ! empty($options['delete']))
        {
            try
            {
                unlink($filename);
            }
            catch (Exception $e)
            {
                Base::getLog()->error($e->getMessage(), [
                    'code' => $e->getCode(),
                ]);
            }
        }

        // 停止执行
        @set_time_limit($prevTimeLimit);
        Base::getHttp()->end();
    }

    /**
     * 渲染当前响应对象
     *
     * @return string
     */
    public function render()
    {
        Base::getLog()->debug(__METHOD__ . ' render response object');

        if ( ! $this->headers('content-type'))
        {
            $this->headers('content-type', Base::$contentType . '; charset=' . $this->charset);
        }

        $this->headers('content-length', (string) $this->contentLength);

        if (Base::$expose)
        {
            $this->headers('user-agent', Base::version());
        }

        if ($this->cookies)
        {
            if (extension_loaded('http'))
            {
                $this->headers('set-cookie', http_build_cookie($this->cookies));
            }
            else
            {
                $cookies = [];
                foreach ($this->cookies as $key => $value)
                {
                    $string = $key . '=' . $value['value'] . '; expires=' . date('l, d M Y H:i:s T', $value['expiration']);
                    $cookies[] = $string;
                }
                $this->headers('set-cookie', $cookies);
            }
        }

        $output = $this->protocol
            . ' '
            . $this->status
            . ' '
            . Arr::get(Http::$text, $this->status)
            . "\r\n";
        $output .= $this->message->headerLines;
        $output .= $this->message->body;

        return $output;
    }

    /**
     * 根据响应内容来生成E-Tag
     *
     * @throws RequestException
     * @return string
     */
    public function generateEtag()
    {
        if ('' === $this->body)
        {
            throw new RequestException('No response yet associated with request - cannot auto generate resource ETag');
        }

        return '"' . sha1($this->render()) . '"';
    }

    /**
     * 解析HTTP_RANGE
     *
     * @return array|false
     */
    protected function _parseByteRange()
    {
        if ($httpRange = Arr::get($_SERVER, 'HTTP_RANGE'))
        {
            Base::getLog()->debug(__METHOD__ . ' get HTTP_RANGE', [
                'string' => $httpRange,
            ]);
            preg_match_all('/(-?[0-9]++(?:-(?![0-9]++))?)(?:-?([0-9]++))?/', $httpRange, $matches, PREG_SET_ORDER);
            return $matches[0];
        }
        else
        {
            return false;
        }
    }

    /**
     * 计算要发送的字节范围
     *
     * @param  int $size
     * @return array
     */
    protected function _calculateByteRange($size)
    {
        $start = 0;
        $end = $size - 1;

        if ($range = $this->_parseByteRange())
        {
            // HTTP_RANGE中读取数据
            $start = Arr::get($range, 1, $start);

            if ('-' === Arr::get($range, 0))
            {
                // 负数表示返回从后面开始算起的字节数
                $start = $size - abs($start);
            }

            $end = Arr::get($range, 2, $end);
        }

        // 格式化数值，保证在范围内
        $start = abs(intval($start));
        $end = min(abs(intval($end)), $size - 1);
        $start = ($end < $start) ? 0 : max($start, 0);

        return [
            $start,
            $end,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->message->protocolVersion;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        $this->message->withHeader($name, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        $this->message->withAddedHeader($name, $value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        $this->message->withoutHeader($name);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $this->status = $code;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        return Arr::get(Http::$text, $this->status, '');
    }
}
