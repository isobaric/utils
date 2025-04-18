<?php

namespace Isobaric\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Throwable;

/**
 * @see self::getJson()
 * @method static getJson(string $url, int $timeout = 5, array $headers = [])
 * @method static getJsonSuccess(string $url, int $timeout = 5, array $headers = [])
 *
 * @see self::postJson()
 * @method static postJson(string $url, array $data = [], int $timeout = 5, array $headers = [])
 * @method static postJsonSuccess(string $url, array $data = [], int $timeout = 5, array $headers = [])
 *
 * @see self::putJson()
 * @method static putJson(string $url, array $data = [], int $timeout = 5, array $headers = [])
 * @method static putJsonSuccess(string $url, array $data = [], int $timeout = 5, array $headers = [])
 *
 *
 * @method static getJsonBatch(array $request = [], int $timeout = 5, array $headers = [])
 *
 * @method static postJsonBatch(array $request = [], int $timeout = 5, array $headers = [])
 * @method static postJsonBatchSuccess(array $request = [], int $timeout = 5, array $headers = [])
 *
 * @method static postFormJson(array $request = [], int $timeout = 5, array $headers = [])
 * @method static postFormJsonBatchSuccess(array $request = [], int $timeout = 5, array $headers = [])
 */
class HttpUtil
{
    // 请求超时时长 单位：秒
    public int $timeout = 60;

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
            // 异常信息记录到log
            $args = '';
            foreach ($arguments as $argument) {
                if (is_array($argument)) {
                    $args .= json_encode($argument, JSON_UNESCAPED_UNICODE) . ' ';
                } else {
                    $args .= $argument . ' ';
                }
            }

            $msg = '第三方接口异常 方法 ' . $name
                . ' 参数 ' . $args
                . ' 异常 ' . $t->getMessage() . ' ' . $t->getFile() . '(' . $t->getLine() . ')' . $t->getCode();

            // TODO 记录Log Log记录方法应该可以被重写
            throw $t;
        }
    }

    /**
     * 返回值处理
     * @param array $response
     * @return mixed
     */
    public static function successJsonDataDecode(array $response): mixed
    {
        // TODO 配置默认message 默认$successCode 默认异常code

        (int)$code = $response['code'] ?? 0;
        (string)$message = $response['msg'] ?? $response['message'] ?? '';
        $successCode = [];
        if (in_array($code, $successCode)) {
            return $response['data'] ?? [];
        }
        throw new RuntimeException($message, $code);
    }

    /**
     * 批量请求结果处理
     * @param array $response
     * @return array
     */
    public static function successBatchResponseDecode(array $response): array
    {
        $result = [];
        foreach ($response as $item) {
            $result[] = self::successJsonDataDecode($item);
        }
        unset($response);
        return array_filter($result);
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
     * @throws GuzzleException
     */
    private function requestBody(string $method, string $url, null|int $timeout, array $headers, array $options = []): string
    {
        if (is_null($timeout)) {
            $options['timeout'] = $this->timeout;
        } else {
            $options['timeout'] = $timeout;
        }

        if (!empty($headers)) {
            $options['headers'] = $headers;
        }

        $client = new Client();
        $response = $client->request($method, $url, $options);

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return $response->getBody();
        }

        // TODO LOG

        throw new RuntimeException($response->getBody());
    }

    /**
     * 请求并json解析返回值
     * @param string $method
     * @param string $url
     * @param int    $timeout
     * @param array  $headers
     * @param array  $options
     * @return mixed
     * @throws GuzzleException
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
     * @throws GuzzleException
     */
    private function requestAndResponseSuccess(string $method, string $url, int $timeout, array $headers, array $options): mixed
    {
        $json = $this->requestAndResponseJson($method, $url, $timeout, $headers, $options);

        if (!is_array($json)) {
            // TODO LOG
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

        // TODO LOG
        throw new RuntimeException($message, $code);
    }

    /**
     * @param string   $url
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     */
    public function get(string $url, null|int $timeout = null, array $headers = []): string
    {
        return $this->requestBody('GET', $url, $timeout, $headers);
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

    // TODO

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

}
