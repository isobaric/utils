<?php

namespace Isobaric\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Throwable;

class HttpUtil
{
    // success方法，返回表示code码的字段名
    public static string $successCodeIndex = 'code';

    // success方法，返回表示数据字段的字段名
    public static string $successDataIndex = 'data';

    // success方法，返回表示提示消息的字段名
    public static string $successMessageIndex = 'message';

    // success方法，返回值中没有表示code码的字段时，默认的赋值
    public static int $successDefaultCode = 200;

    // success方法，返回值中没有表示提示消息的字段时，默认的赋值
    public static string $successDefaultMessage = '';

    // success方法，默认的成功码|
    public static array $successCodes = [
        200
    ];

    /**
     * @param string $index
     * @return void
     */
    public static function setSuccessCodeIndex(string $index = 'code'): void
    {
        self::$successCodeIndex = $index;
    }

    /**
     * @param string $index
     * @return void
     */
    public static function setSuccessDataIndex(string $index = 'data'): void
    {
        self::$successDataIndex = $index;
    }

    /**
     * @param string $index
     * @return void
     */
    public static function setSuccessMessageIndex(string $index = 'message'): void
    {
        self::$successMessageIndex = $index;
    }

    /**
     * @param int $code
     * @return void
     */
    public static function setSuccessDefaultCode(int $code = 200): void
    {
        self::$successDefaultCode = $code;
        self::$successCodes = [$code];
    }

    /**
     * @param string $message
     * @return void
     */
    public static function setSuccessDefaultMessage(string $message = ''): void
    {
        self::$successDefaultMessage = $message;
    }

    /**
     * @param array $codes
     * @return void
     */
    public static function setSuccessCodes(int ...$codes): void
    {
        self::$successCodes = $codes;
    }

    /**
     * 异常信息记录
     * @param array          $arguments
     * @param Throwable|null $throwable
     * @param mixed         $response
     * @return void
     */
    public static function log(array $arguments, ?Throwable $throwable = null, mixed $response = null): void
    {
        $logStr = 'HttpUtil Error Log: ';

        // 异常信息记录到log
        if (!empty($arguments)) {
            foreach ($arguments as $argumentName => $argument) {
                $logStr .= $argumentName . ': ';
                if (is_array($argument)) {
                    $logStr .= json_encode($argument, JSON_UNESCAPED_UNICODE) . '; ';
                } else {
                    $logStr .= $argument . '; ';
                }
            }
        }


        if (!is_null($throwable)) {
            $logStr .= 'Throwable: ' .
                $throwable->getMessage()
                . $throwable->getFile()
                . '(' . $throwable->getLine() . ')'
                . $throwable->getCode()
                . '; ';
        }

        $logStr .= 'Response: ';
        if (is_array($response)) {
            $logStr .= json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            $logStr .= $response;
        }
        $logStr .= ';';

        echo $logStr . PHP_EOL;
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
        $arguments = get_defined_vars();
        try {
            if (!is_null($timeout)) {
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
            self::log($arguments, $throwable);

            throw $throwable;
        }
    }

    /**
     * 请求并json解析返回值
     * @param string   $method
     * @param string   $url
     * @param int|null $timeout
     * @param array    $headers
     * @param array    $options
     * @return mixed
     * @throws GuzzleException
     * @throws Throwable
     */
    private static function requestJson(string $method, string $url, null|int $timeout, array $headers, array $options): mixed
    {
        $body = self::requestBody($method, $url, $timeout, $headers, $options);
        return json_decode($body, true);
    }

    /**
     * 请求并json解析后返回表示成功的值
     * @param string   $method
     * @param string   $url
     * @param int|null $timeout
     * @param array    $headers
     * @param array    $options
     * @return mixed
     * @throws GuzzleException
     * @throws Throwable
     */
    private static function requestSuccess(string $method, string $url, null|int $timeout, array $headers, array $options): mixed
    {
        $arguments = get_defined_vars();
        $jsonResponse = self::requestJson($method, $url, $timeout, $headers, $options);

        return self::successDecode($arguments, $jsonResponse);
    }

    /**
     * @param array $arguments
     * @param mixed $jsonResponse
     * @return mixed
     */
    private static function successDecode(array $arguments, mixed $jsonResponse): mixed
    {
        if (!is_array($jsonResponse)) {
            // 异常信息记录
            self::log($arguments, null, $jsonResponse);

            throw new RuntimeException('Unsupported Response Body');
        }

        // code码
        (int)$code = $jsonResponse[self::$successCodeIndex] ?? self::$successDefaultCode;

        // 提示消息
        (string)$message = $jsonResponse[self::$successMessageIndex] ?? self::$successDefaultMessage;

        // 成功时 返回指定的消息
        if (in_array($code, self::$successCodes)) {
            return $jsonResponse[self::$successDataIndex] ?? [];
        }

        // 异常信息记录
        self::log($arguments, null, $jsonResponse);

        throw new RuntimeException($message, $code);
    }

    /**
     * 发送请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     * @throws Throwable
     */
    public static function get(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('GET', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * 发送请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     * @throws Throwable
     */
    public static function post(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('POST', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * 发送请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException|Throwable
     */
    public static function put(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('PUT', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * 发送请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException|Throwable
     */
    public static function head(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('HEAD', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * 发送请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     * @throws Throwable
     */
    public static function patch(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('PATCH', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * 发送请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     * @throws Throwable
     */
    public static function delete(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('DELETE', $url, $timeout, $headers, ['query' => $data]);
    }

    /**
     * 发送Form请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     * @throws Throwable
     */
    public static function getForm(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('GET', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     * @throws Throwable
     */
    public static function postForm(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('POST', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException|Throwable
     */
    public static function putForm(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('PUT', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException|Throwable
     */
    public static function headForm(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('HEAD', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     * @throws Throwable
     */
    public static function patchForm(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('PATCH', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并返回结果
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return string
     * @throws GuzzleException
     * @throws Throwable
     */
    public static function deleteForm(string $url, array $data = [], null|int $timeout = null, array $headers = []): string
    {
        return self::requestBody('DELETE', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function getJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('GET', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function postJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('POST', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function putJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('PUT', $url, $timeout, $headers, ['json' => $data]);
    }


    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function headJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('HEAD', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function deleteJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('DELETE', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function getFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('GET', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function postFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('POST', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function putFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('PUT', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function headFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('HEAD', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function patchFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('PATCH', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送Form请求，并JSON解析返回值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function deleteFormJson(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestJson('DELETE', $url, $timeout, $headers, ['form_params' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function getJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestSuccess('GET', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function postJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestSuccess('POST', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function putJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestSuccess('PUT', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function headJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestSuccess('HEAD', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function patchJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestSuccess('PATCH', $url, $timeout, $headers, ['json' => $data]);
    }

    /**
     * 发送JSON请求，并JSON解析后返回值指定的值
     * @param string   $url
     * @param array    $data
     * @param int|null $timeout
     * @param array    $headers
     * @return mixed
     * @throws GuzzleException|Throwable
     */
    public static function deleteJsonSuccess(string $url, array $data = [], null|int $timeout = null, array $headers = []): mixed
    {
        return self::requestSuccess('DELETE', $url, $timeout, $headers, ['json' => $data]);
    }
}
