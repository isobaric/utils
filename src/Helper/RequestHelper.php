<?php

namespace Horseloft\Utils\Helper;

use DateTime;
use Throwable;

abstract class RequestHelper
{
    // 待验证的全部参数
    protected $verifyParams;

    // 当前校验的条件 in/min/max/length/regex/mime
    protected $verifyFactor;

    // 参数的别名
    protected $verifyAlias;

    // 当前校验的类型
    protected $verifyType;

    // 当前的校验规则
    protected $verifyRule;

    // 当前校验的字段值要求
    protected $verifyPermit;

    // 当前校验的参数的值
    protected $paramValue;

    // 当前校验的参数的值
    protected $paramKey;

    /**
     * 当前校验的文件名称（不含后缀）
     *
     * @var array|string|null
     */
    public $filename;

    /**
     * 当前校验的文件后缀名
     *
     * @var array|string|null
     */
    public $extension;

    /**
     * 当前校验的文件MIME
     *
     * @var array|string|null
     */
    public $mime;

    /**
     * @var string[]
     */
    protected $defaultType = [
        'bool',
        'int',
        'numeric',
        'string',
        'array',
        'list',
        'date',
        'file',
        'email',
        'json',
        'url',
        'ip',
    ];

    /**
     * @var string[]
     */
    protected $defaultPermit = [
        'nullable',
        'empty',
        'must',
        'lost'
    ];

    /**
     * bool类型参数校验
     *
     * @return bool
     */
    protected function isBool(): bool
    {
        switch ($this->verifyPermit) {
            case 'must':
                return is_bool($this->paramValue);
            case 'empty':
                return is_bool($this->paramValue) || $this->isEmptyString();
            case 'nullable':
                return is_bool($this->paramValue) || is_null($this->paramValue);
            default:
                if (array_key_exists($this->paramKey, $this->verifyParams)) {
                    return is_bool($this->paramValue) || is_null($this->paramValue) || $this->isEmptyString();
                }
                return true;
        }
    }

    /**
     * 通用校验
     *
     * @param string $call
     * @param null $args
     *
     * @return bool
     */
    protected function multipleVerify(string $call, $args = null): bool
    {
        switch ($this->verifyPermit) {
            case 'must':
                return call_user_func([$this, $call], $args);
            case 'empty':
                return $this->isEmptyString() || call_user_func([$this, $call], $args);
            case 'nullable':
                return is_null($this->paramValue) || call_user_func([$this, $call], $args);
            default:
                if (array_key_exists($this->paramKey, $this->verifyParams)) {
                    return is_null($this->paramValue) || $this->isEmptyString() || call_user_func([$this, $call], $args);
                }
                return true;
        }
    }

    /**
     * 判断一维数组
     *
     * @return false
     */
    protected function isArray(): bool
    {
        switch ($this->verifyPermit) {
            case 'must':
                return $this->arrayComplexVerify();
            case 'empty':
                return (is_array($this->paramValue) && empty($this->paramValue)) || $this->arrayComplexVerify();
            case 'nullable':
                return is_null($this->paramValue) || $this->arrayComplexVerify();
            default:
                if (array_key_exists($this->paramKey, $this->verifyParams)) {
                    return is_null($this->paramValue)
                        || (is_array($this->paramValue) && empty($this->paramValue))
                        || $this->arrayComplexVerify();
                }
                return true;
        }
    }

    /**
     * 数组是否为顺序列表
     *
     * @return bool
     */
    protected function isList(): bool
    {
        switch ($this->verifyPermit) {
            case 'must':
                return $this->listComplexVerify();
            case 'empty':
                return (is_array($this->paramValue) && empty($this->paramValue)) || $this->listComplexVerify();
            case 'nullable':
                return is_null($this->paramValue) || $this->listComplexVerify();
            default:
                if (array_key_exists($this->paramKey, $this->verifyParams)) {
                    return is_null($this->paramValue)
                        || (is_array($this->paramValue) && empty($this->paramValue))
                        || $this->listComplexVerify();
                }
                return true;
        }
    }

    /**
     * 数字类型的其他规则校验
     *
     * @param bool $isInt
     * @return bool
     */
    private function numberComplexVerify(bool $isInt): bool
    {
        if ($isInt && !$this->isIntNumber($this->paramValue)) {
            return false;
        }
        if (!$isInt && !is_numeric($this->paramValue)) {
            return false;
        }
        // in条件校验规则
        $this->verifyFactor = 'in';
        $inValue = array_filter($this->getVerifyInValue(), function ($item) use ($isInt) {
            return $isInt ? $this->isIntNumber($item) : is_numeric($item);
        });
        if (!empty($inValue) && !in_array($this->paramValue, $inValue, true)) {
            return false;
        }
        // regex
        $this->verifyFactor = 'regex';
        $regex = $this->getVerifyRegexValue();
        if (!is_null($regex) && !preg_match($regex, $this->paramValue)) {
            return false;
        }
        // min max length
        return $this->numberCompare($this->paramValue, false, $isInt);
    }

    /**
     * 字符串类型的其他规则校验
     *
     * @return bool
     */
    private function stringComplexVerify(): bool
    {
        // 允许是字符串或者数字
        if (!is_string($this->paramValue) && !is_numeric($this->paramValue)) {
            return false;
        }
        // in条件校验规则
        $this->verifyFactor = 'in';
        $inValue = array_filter($this->getVerifyInValue(), function ($item) {
            return is_string($item) || is_numeric($item);
        });
        if (!empty($inValue) && !in_array($this->paramValue, $inValue, true)) {
            return false;
        }
        // regex
        $this->verifyFactor = 'regex';
        $regex = $this->getVerifyRegexValue();
        if (!is_null($regex) && !preg_match($regex, $this->paramValue)) {
            return false;
        }
        // min max length
        return $this->numberCompare(mb_strlen($this->paramValue), true);
    }

    /**
     * 一维数组类型的其他规则校验
     *
     * @return bool
     */
    private function arrayComplexVerify(): bool
    {
        if (!is_array($this->paramValue)) {
            return false;
        }
        return $this->numberCompare(count($this->paramValue), true);
    }

    /**
     * 数组列表的其他规则校验
     *
     * @return bool
     */
    private function listComplexVerify(): bool
    {
        if (!is_array($this->paramValue)) {
            return false;
        }
        $i = -1;
        foreach ($this->paramValue as $k => $v) {
            ++$i;
            if ($k !== $i) {
                return false;
            }
        }
        return $this->numberCompare(count($this->paramValue), true);
    }

    /**
     * 日期的参数校验
     *
     * @return bool
     */
    private function dateComplexVerify(): bool
    {
        $format = $this->getVerifyDateFormat();
        if (!$this->isDateValue($this->paramValue, $format)) {
            return false;
        }
        // in条件校验规则
        $this->verifyFactor = 'in';
        $inValue = array_filter($this->getVerifyInValue(), function ($item) use($format) {
            return $this->isDateValue($item, $format);
        });
        if (!empty($inValue) && !in_array($this->paramValue, $inValue, true)) {
            return false;
        }
        $timestamp = $this->getVerifyDateTimestamp($this->paramValue, $format);
        // min
        $this->verifyFactor = 'min';
        $min = $this->getVerifyDateValue('min', $format);
        if (!is_null($min) && $timestamp < $min) {
            return false;
        }
        // max
        $this->verifyFactor = 'max';
        $max = $this->getVerifyDateValue('max', $format);
        if (!is_null($max) && $timestamp > $max) {
            return false;
        }
        // length
        $this->verifyFactor = 'length';
        $length = $this->getVerifyIntValue('length', true);
        if (!is_null($length) && $length != mb_strlen($this->paramValue)) {
            return false;
        }
        // regex
        $this->verifyFactor = 'regex';
        $regex = $this->getVerifyRegexValue();
        if (!is_null($regex) && !preg_match($regex, $this->paramValue)) {
            return false;
        }
        return true;
    }

    /**
     * 文件校验
     *
     * @return bool
     */
    private function fileComplexVerify(): bool
    {
        // 上传文件必须是数组 可能是多文件上传
        if (!is_array($this->paramValue) || !isset($this->paramValue['tmp_name'])) {
            return false;
        }
        if (is_array($this->paramValue['tmp_name'])) { // 多文件上传
            $isSingle = false;
            $fileNumber = count($this->paramValue['tmp_name']);
        } else { // 单文件上传 格式转为多文件模式
            $this->paramValue = [
                'name' => [$this->paramValue['name']],
                'type' => [$this->paramValue['type']],
                'tmp_name' => [$this->paramValue['tmp_name']],
                'error' => [$this->paramValue['error']],
                'size' => [$this->paramValue['size']],
            ];
            $isSingle = true;
            $fileNumber = 1;
        }
        for ($key = 0; $key < $fileNumber; $key++) {
            // 判断文件是否真实上传
            $this->verifyFactor = 'upload';
            if (!is_uploaded_file($this->paramValue['tmp_name'][$key])) {
                return false;
            }
            // 上传发生错误
            $this->verifyFactor = 'file_error';
            if ($this->paramValue['error'][$key] > 0) {
                return false;
            }
            // MIME校验
            $this->verifyFactor = 'mime';
            $fileMime = mime_content_type($this->paramValue['tmp_name'][$key]);
            $mine = $this->getVerifyMimeValue();
            if (!empty($mine) && !in_array($fileMime, $mine)) {
                return false;
            }
            // 上传文件最小size
            $this->verifyFactor = 'min';
            $min = $this->getVerifyIntValue('min', true);
            if (!is_null($min) && $this->paramValue['size'][$key] < $min) {
                return false;
            }
            // 上传文件最大size
            $this->verifyFactor = 'max';
            $max = $this->getVerifyIntValue('max', true);
            if (!is_null($max) && $this->paramValue['size'][$key] > $max) {
                return false;
            }
            // 文件信息
            $pathInfo = pathinfo($this->paramValue['name'][$key]);
            if ($isSingle) {
                // 当前校验的文件名称（不含后缀）
                $this->filename = $pathInfo['filename'];
                // 当前校验的文件后缀名
                $this->extension = $pathInfo['extension'] ?? '';
                // MIME
                $this->mime = $fileMime;
            } else {
                // 当前校验的文件名称（不含后缀）
                $this->filename[] = $pathInfo['filename'];
                // 当前校验的文件后缀名
                $this->extension[] = $pathInfo['extension'] ?? '';
                // MIME
                $this->mime[] = $fileMime;
            }
        }
        return true;
    }

    /**
     * json校验
     *
     * @return bool
     */
    private function jsonComplexVerify(): bool
    {
        try {
            json_decode($this->paramValue);
            if (json_last_error() != JSON_ERROR_NONE) {
                return false;
            }
        } catch (Throwable $e) {
            return false;
        }
        return $this->numberCompare(mb_strlen($this->paramValue), true);
    }

    /**
     * 正则校验
     *  用于校验 email url ip
     *
     * @param array $regular
     * @return bool
     */
    private function regexComplexVerify(array $regular): bool
    {
        $regex = $this->getVerifyRegexValue();
        if (is_null($regex)) {
            $pattern = $regular;
        } else {
            $pattern = [$regex];
        }
        if (!$this->isRegexValue($pattern, $this->paramValue)) {
            return false;
        }
        // in条件校验规则
        $this->verifyFactor = 'in';
        $inValue = array_filter($this->getVerifyInValue(), function ($item) use ($pattern) {
            return $this->isRegexValue($pattern, $item);
        });
        if (!empty($inValue) && !in_array($this->paramValue, $inValue, true)) {
            return false;
        }
        return $this->numberCompare(mb_strlen($this->paramValue), true);
    }

    /**
     * 数字大小比较
     *  用户 min max length 的比较
     *
     * @param $number
     * @param bool $isPositive
     * @param bool $isInt
     *
     * @return bool
     */
    private function numberCompare($number, bool $isPositive = false, bool $isInt = true): bool
    {
        // min
        $this->verifyFactor = 'min';
        $min = $isInt ? $this->getVerifyIntValue('min', $isPositive) : $this->getVerifyNumericValue('min');
        if (!is_null($min) && $number < $min) {
            return false;
        }
        // max
        $this->verifyFactor = 'max';
        $max = $isInt ? $this->getVerifyIntValue('max', $isPositive) : $this->getVerifyNumericValue('max');
        if (!is_null($max) && $number > $max) {
            return false;
        }
        // length
        $this->verifyFactor = 'length';
        $length = $this->getVerifyIntValue('length', $isPositive);
        if (!is_null($length) && $number != $length) {
            return false;
        }
        return true;
    }

    /**
     * 获取校验规则中in的值
     *
     * @return array
     */
    private function getVerifyInValue(): array
    {
        if (array_key_exists('in', $this->verifyRule) && is_array($this->verifyRule['in'])) {
            return $this->verifyRule['in'];
        }
        return [];
    }

    /**
     * 获取校验规则中min的值
     *
     * @param string $key
     * @param bool $isPositive
     *
     * @return int|null
     */
    private function getVerifyIntValue(string $key, bool $isPositive = false): ?int
    {
        if (array_key_exists($key, $this->verifyRule) && (is_int($this->verifyRule[$key]))) {
            if ($isPositive && $this->verifyRule[$key] < 0) {
                return null;
            }
            return $this->verifyRule[$key];
        }
        return null;
    }

    /**
     * 获取校验规则中regex的值
     *
     * @return string|null
     */
    private function getVerifyRegexValue(): ?string
    {
        if (array_key_exists('regex', $this->verifyRule) && (is_string($this->verifyRule['regex']))) {
            return $this->verifyRule['regex'];
        }
        return null;
    }

    /**
     * 获取字符串校验规则中的值
     *
     * @return array|null
     */
    private function getVerifyMimeValue(): ?array
    {
        if (array_key_exists('mime', $this->verifyRule) && (is_array($this->verifyRule['mime']))) {
            return $this->verifyRule['mime'];
        }
        return null;
    }

    /**
     * 获取校验规则中的numerical值
     *
     * @param string $key
     *
     * @return float|int|string|null
     */
    private function getVerifyNumericValue(string $key)
    {
        if (array_key_exists($key, $this->verifyRule) && is_numeric($this->verifyRule[$key])) {
            return $this->verifyRule[$key];
        }
        return null;
    }

    /**
     * 获取参数校验中的日期的值
     *
     * @param string $key
     * @param string $format
     *
     * @return int|null
     */
    private function getVerifyDateValue(string $key, string $format): ?int
    {
        if (array_key_exists($key, $this->verifyRule) && $this->isDateValue($this->verifyRule[$key], $format)) {
            return $this->getVerifyDateTimestamp($this->verifyRule[$key], $format);
        }
        return null;
    }

    /**
     * 获取校验规则中Date的时间戳
     *
     * @param string $string
     * @param string $format
     *
     * @return int
     */
    private function getVerifyDateTimestamp(string $string, string $format): int
    {
        $date = DateTime::createFromFormat($format, $string);
        return $date->getTimestamp();
    }

    /**
     * 获取校验的日期参数的format格式
     *
     * @return string
     */
    private function getVerifyDateFormat(): string
    {
        if (array_key_exists('format', $this->verifyRule) && (is_string($this->verifyRule['format']))) {
            return $this->verifyRule['format'];
        }
        return 'Y-m-d H:i:s';
    }

    /**
     * 校验日期格式是否合法
     *
     * @param string $string
     * @param string $format
     *
     * @return bool
     */
    private function isDateValue(string $string, string $format): bool
    {
        $date = DateTime::createFromFormat($format, $string);
        return $date && $date->format($format) == $string;
    }

    /**
     * 校验正则参数
     *
     * @param array $pattern
     * @param string $value
     *
     * @return bool
     */
    private function isRegexValue(array $pattern, string $value): bool
    {
        foreach ($pattern as $regex) {
            if (preg_match($regex, $value)){
                return true;
            }
        }
        return false;
    }

    /**
     * 是否空字符串
     *
     * @return bool
     */
    private function isEmptyString(): bool
    {
        return (is_string($this->paramValue) || is_numeric($this->paramValue)) && mb_strlen($this->paramValue) == 0;
    }

    /**
     * 是否是整数
     *
     * @param $number
     * @return bool
     */
    private function isIntNumber($number): bool
    {
        if (is_int($number)) {
            return true;
        }
        if (!is_numeric($number)) {
            return false;
        }
        return intval($number) == $number;
    }
}
