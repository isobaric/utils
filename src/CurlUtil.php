<?php
namespace Isobaric\Utils;

class CurlUtil
{
    private string $url = '';
    private int $code = -1;
    private int $timeout;
    private string $method = '';
    private array $header = [];
    private array $customize = [];
    private bool $isMillisecond = false;

    /**
     * @var false|resource
     */
    private $handle;

    /**
     * @param int $timeout
     */
    public function __construct(int $timeout = 30)
    {
        $this->timeout = $timeout;
        $this->handle = curl_init();
    }

    /**
     * 关闭句柄
     */
    public function __destruct()
    {
        curl_close($this->handle);
    }

    /**
     * set http header
     *
     * @param array $header
     *  $header =  [
     *   'Content-Type: application/json',
     *   'Host: www.baidu.com',
     *   'Referer: https://www.baidu.com',
     *  ]
     * @return $this
     */
    public function setHeader(array $header): CurlUtil
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 设置超时时间
     *
     * @param int $timeout
     * @param bool $isMillisecond false毫秒 true秒
     *
     * @return $this
     */
    public function setTimeout(int $timeout, bool $isMillisecond = false): CurlUtil
    {
        $this->timeout = $timeout;
        $this->isMillisecond = $isMillisecond;
        return $this;
    }

    /**
     * 设置自定义option
     *
     * @param array $option
     *
     * @return $this
     */
    public function setOption(array $option): CurlUtil
    {
        $this->customize = $option;
        return $this;
    }

    /**
     * GET
     *
     * @param string $url
     *
     * @return bool|string
     */
    public function get(string $url): bool|string
    {
        $this->url = $url;
        $this->method = 'GET';
        return $this->response();
    }

    /**
     * POST
     *
     * @param string $url
     * @param $data
     *
     * @return bool|string
     */
    public function post(string $url, $data): bool|string
    {
        $this->url = $url;
        $this->method = 'POST';
        return $this->response($data);
    }

    /**
     * HTTP Response Code
     *
     * @return int
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * POST参数组装
     *
     * @param $data
     *
     * @return void
     */
    private function postDataBuild($data): void
    {
        if (empty($data)) {
            return;
        }
        if (is_array($data)) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);
        }
    }

    /**
     * 设置基础option
     * @return array
     */
    private function getBaseOption(): array
    {
        return [
            // 设置URL
            CURLOPT_URL => $this->url,
            //  // 允许重定向的最大次数
            CURLOPT_MAXREDIRS => 3,
            // 允许重定向
            CURLOPT_FOLLOWLOCATION => true,
            // 将curl_exec()获取的信息以字符串返回，而不是直接输出
            CURLOPT_RETURNTRANSFER => true,
        ];
    }

    /**
     * 获取CURL请求
     *
     * @param $data
     *
     * @return bool|string
     */
    private function response($data = null): bool|string
    {
        // 重置所有的预先设置的选项
        curl_reset($this->handle);

        // 设置基础option
        $options = $this->getBaseOption();

        // HTTPS证书验证
        if (str_starts_with($this->url, "https")) {
            // 默认 SSL不验证证书
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            // 默认 SSL不验证HOST
            $options[CURLOPT_SSL_VERIFYHOST] = false;
        }

        // 设置CURL的header头
        if (!empty($this->header)) {
            $options[CURLOPT_HTTPHEADER] = $this->header;
        }

        // 设置超时时间
        if ($this->isMillisecond) {
            // 以毫秒为单位
            $options[CURLOPT_TIMEOUT_MS] = $this->timeout;
        } else {
            // 以秒为单位
            $options[CURLOPT_TIMEOUT] = $this->timeout;
        }

        // get / post 参数设置
        if ($this->method != 'GET') {
            $options[CURLOPT_POST] = true;
            $this->postDataBuild($data);
        }

        // 设置CURL参数
        curl_setopt_array($this->handle, $options);

        // 自定义配置项
        if ($this->customize) {
            curl_setopt_array($this->handle, $this->customize);
        }

        // 响应值
        $response = curl_exec($this->handle);

        // 响应码
        $this->code = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

        return $response;
    }
}
