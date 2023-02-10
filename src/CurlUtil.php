<?php
namespace Horseloft\Utils;

class CurlUtil
{
    private $url = '';
    private $code = -1;
    private $timeout;
    private $method = '';
    private $header = [];
    private $option = [];
    private $customize = [];
    private $isMillisecond = false;

    /**
     * @var false|resource
     */
    private $handle;

    public function __construct(int $timeout = 30)
    {
        $this->timeout = $timeout;
        $this->handle = curl_init();
    }

    /**
     * set http header
     *
     * @param array $header
     *
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
    public function get(string $url)
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
    public function post(string $url, $data)
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
    private function postDataBuild($data)
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
     *
     * @return void
     */
    private function setBaseOption()
    {
        // 设置URL
        $this->option[CURLOPT_URL] = $this->url;
        // 允许重定向
        $this->option[CURLOPT_FOLLOWLOCATION] = true;
        // 允许重定向的最大次数
        $this->option[CURLOPT_MAXREDIRS] = 3;
    }

    /**
     * HTTPS证书验证
     *
     * @return void
     */
    private function setHttpsOption()
    {
        if (preg_match("/^https/", $this->url)) {
            // SSL不验证证书
            $this->option[CURLOPT_SSL_VERIFYPEER] = false;
            // SSL不验证HOST
            $this->option[CURLOPT_SSL_VERIFYHOST] = false;
        }
    }

    /**
     * 设置CURL的header头
     *
     * @return void
     */
    private function setHeaderOption()
    {
        if (count($this->header) > 0) {
            $this->option[CURLOPT_HTTPHEADER] = $this->header;
        }
    }

    /**
     * 设置为POST发送
     *
     * @return void
     */
    private function setPostOption($data)
    {
        if ($this->method != 'GET') {
            $this->option[CURLOPT_POST] = true;
            $this->postDataBuild($data);
        }
    }

    /**
     * 设置自定义option
     *
     * @return void
     */
    private function setCustomizeOption()
    {
        foreach ($this->customize as $key => $value) {
            $this->option[$key] = $value;
        }
    }

    /**
     * 超时设置
     *
     * @return void
     */
    private function setTimeoutOption()
    {
        if ($this->isMillisecond) {
            // 设置超时时间，以毫秒为单位
            $this->option[CURLOPT_TIMEOUT_MS] = $this->timeout;
        } else {
            // 设置超时时间，以秒为单位
            $this->option[CURLOPT_TIMEOUT] = $this->timeout;
        }
    }

    /**
     * 获取CURL请求
     *
     * @param $data
     *
     * @return bool|string
     */
    private function response($data = null)
    {
        //设置基础option
        $this->setBaseOption();
        // HTTPS证书验证
        $this->setHttpsOption();
        // 设置CURL的header头
        $this->setHeaderOption();
        // 设置为POST发送
        $this->setPostOption($data);
        // 超时设置
        $this->setTimeoutOption();
        // 设置自定义option
        $this->setCustomizeOption();
        // 设置CURL参数
        curl_setopt_array($this->handle, $this->option);

        // 响应值
        $response = curl_exec($this->handle);

        // 响应码
        $this->code = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

        // 关闭句柄
        curl_close($this->handle);

        return $response;
    }
}
