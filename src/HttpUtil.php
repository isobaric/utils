<?php

namespace Isobaric\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Throwable;

class HttpUtil
{
    // 请求超时时长 单位：秒
    public static int $timeout = 60;

    // success方法，返回表示code码的字段名
    public string $successCodeIndex = 'code';

    // success方法，返回表示数据字段的字段名
    public string $successDataIndex = 'data';

    // success方法，返回表示提示消息的字段名
    public string $successMessageIndex = 'message';

    // success方法，返回值中没有表示code码的字段时，默认的赋值
    public int $defaultResponseCode = 0;

    // success方法，返回值中没有表示提示消息的字段时，默认的赋值
    public string $defaultResponseMessage = '';

    public array $defaultSuccessCode = [
        200
    ];

    // 当前类对象
    private static null|HttpUtil $httpUtil = null;

    /**
     * 以静态的形式访问方法
     * @param string $name
     * @param array  $arguments
     * @return mixed
     * @throws Throwable
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (is_null(self::$httpUtil)) {
            self::$httpUtil = new self;
        }

        try {
            return self::$httpUtil->$name(...$arguments);
        } catch (Throwable $t) {
            // 异常信息记录
            self::$httpUtil->log($name, $arguments, $t);

            throw $t;
        }
    }

    /**
     * 异常信息记录
     * @param array          $arguments
     * @param Throwable|null $throwable
     * @return void
     */
    public static function log(array $arguments, ?Throwable $throwable = null): void
    {
        // 异常信息记录到log
        $argsStr = '';
        foreach ($arguments as $argument) {
            if (is_array($argument)) {
                $argsStr .= json_encode($argument, JSON_UNESCAPED_UNICODE) . ' ';
            } else {
                $argsStr .= $argument . ' ';
            }
        }

        $msg = '接口异常 ' . $argsStr . ' ' . $throwable->getMessage()
            . $throwable->getFile() . '(' . $throwable->getLine() . ')'
            . $throwable->getCode();

        echo $msg . PHP_EOL;
    }

    /**
     * @param string   $method
     * @param string   $url
     * @param int|null $timeout
     * @param array    $headers
     *
     *  $headers = [
     *      'User-Agent' => 'testing/1.0',
     *      'Accept'     => 'application/json',
     *      'X-Foo'      => ['Bar', 'Baz']
     *  ];
     *
     * @param array    $options
     * @return string
     * @throws GuzzleException|Throwable
     */
    private static function requestBody(string $method, string $url, null|int $timeout, array $headers, array $options = []): string
    {
        try {
            if (is_null($timeout)) {
                $options['timeout'] = self::$timeout;
            } else {
                $options['timeout'] = $timeout;
            }

            if (!empty($headers)) {
                $options['headers'] = $headers;
            }

            // disable throwing exceptions on an HTTP protocol errors
            $options['http_errors'] = false;

            $client = new Client();
            $response = $client->request($method, $url, $options);

            //if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            //    return $response->getBody();
            //}

            return $response->getBody();

        } catch (Throwable $throwable) {
            // 异常信息记录
            self::log(func_get_args(), $throwable);

            throw $throwable;
        }
    }

    /**
     * 请求并json解析返回值
     * @param string $method
     * @param string $url
     * @param int    $timeout
     * @param array  $headers
     * @param array  $options
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    private function requestAndResponseJson(string $method, string $url, int $timeout, array $headers, array $options): mixed
    {
        $body = $this->requestBody($method, $url, $timeout, $headers, $options);
        return json_decode($body, true);
    }

    /**
     * 请求并json解析返回值
     * @param string $method
     * @param string $url
     * @param int    $timeout
     * @param array  $headers
     * @param array  $options
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    private function requestAndResponseSuccess(string $method, string $url, int $timeout, array $headers, array $options): mixed
    {
        $json = $this->requestAndResponseJson($method, $url, $timeout, $headers, $options);

        if (!is_array($json)) {
            // 异常信息记录
            $this->log(func_get_args());

            throw new RuntimeException('Unsupported Response Body');
        }

        // code码
        (int)$code = $response[$this->successCodeIndex] ?? $this->defaultResponseCode;

        // 提示消息
        (string)$message = $response[$this->successMessageIndex] ?? $this->defaultResponseMessage;

        // 成功时 返回指定的消息
        if (in_array($code, $this->defaultSuccessCode)) {
            return $response[$this->successDataIndex] ?? [];
        }

        // 异常信息记录
        $this->log(func_get_args());

        throw new RuntimeException($message, $code);
    }

    /** TODO
     * @param string   $url
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException|Throwable
     */
    public static function get(string $url, null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('GET', $url, $timeout, $headers);
    }

    /**
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     */
    public function post(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return $this->requestBody('POST', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     */
    public function put(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return $this->requestBody('PUT', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     */
    public function head(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return $this->requestBody('HEAD', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     */
    public function patch(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return $this->requestBody('PATCH', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     */
    public function delete(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return $this->requestBody('DELETE', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function getJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('GET', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function postJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('POST', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function putJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('PUT', $url, $timeout, $headers, ['json' => $data]);
    }


    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function headJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('HEAD', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function deleteJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('DELETE', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function getFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('GET', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function postFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('POST', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function putFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('PUT', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function headFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('HEAD', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function patchFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('PATCH', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function deleteFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseJson('DELETE', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function getJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseSuccess('GET', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function postJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseSuccess('POST', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function putJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseSuccess('PUT', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function headJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseSuccess('HEAD', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function patchJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseSuccess('PATCH', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException
     */
    public function deleteJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return $this->requestAndResponseSuccess('DELETE', $url, $timeout, $headers, ['json' => $data]);
    }
}
