<?php

namespace Horseloft\Utils\Helper;

use DateTime;
use Throwable;

abstract class RequestHelper
{
    // 待验证的全部参数
    protected $verifyParams;

    // 当前校验的类型
    protected $verifyType;

    // 当前的校验规则
    protected $verifyRule;

    // 当前校验规则的错误提示
    protected $message = '';

    // 当前校验的字段值要求
    protected $verifyPermit;

    // 当前校验的参数的值
    protected $paramValue;

    // 当前校验的文件名称（不含后缀）
    public $filename;

    // 当前校验的文件后缀名
    public $extension;

    // 当前校验的文件MIME
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
    ];

    /**
     * bool类型参数校验
     *
     * @return bool
     */
    protected function isBool(): bool
    {
        // must 参数的值必须是bool类型
        if ($this->verifyPermit == 'must') {
            return is_bool($this->paramValue);
        }
        // nullable/empty 参数值可以是Null和bool
        return in_array($this->paramValue, [null, false, true], true);
    }

    /**
     * 判断整数
     *
     * @return bool
     */
    protected function isInt(): bool
    {
        // 值必须是int
        if ($this->verifyPermit == 'must') {
            return $this->numberComplexVerify();
        }
        // nullable/empty 参数值可以是Null和int
        if (is_null($this->paramValue)) { // 如果允许Null值，并且值为Null，则不再校验其他规则
            return true;
        }
        return $this->numberComplexVerify();
    }

    /**
     * 判断数字
     *
     * @return bool
     */
    protected function isNumeric(): bool
    {
        // 值必须是数字
        if ($this->verifyPermit == 'must') {
            return $this->numberComplexVerify(false);
        }
        // nullable/empty 参数值可以是Null和numeric
        if (is_null($this->paramValue)) { // 如果允许Null值，并且值为Null，则不再校验其他规则
            return true;
        }
        return $this->numberComplexVerify(false);
    }

    /**
     * 判断字符串
     *
     * @return bool
     */
    protected function isString(): bool
    {
        // 值必须是字符串
        if ($this->verifyPermit == 'must') {
            return $this->stringComplexVerify();
        }
        // nullable/empty 参数值可以是Null和string
        if (is_null($this->paramValue)) {
            return true;
        }
        return $this->stringComplexVerify();
    }

    /**
     * 判断一维数组
     *
     * @return false
     */
    protected function isArray(): bool
    {
        // 值必须是字符串
        if ($this->verifyPermit == 'must') {
            return $this->arrayComplexVerify();
        }
        // nullable/empty 参数值可以是Null和array
        if (is_null($this->paramValue)) {
            return true;
        }
        return $this->arrayComplexVerify();
    }

    /**
     * 数组是否为顺序列表
     *
     * @return bool
     */
    protected function isList(): bool
    {
        // 值必须是字符串
        if ($this->verifyPermit == 'must') {
            return $this->listComplexVerify();
        }
        // nullable/empty 参数值可以是Null和list
        if (is_null($this->paramValue)) {
            return true;
        }
        return $this->listComplexVerify();
    }

    /**
     * 日期格式校验
     *
     * @return bool
     */
    protected function isDate(): bool
    {
        // 值必须是日期
        if ($this->verifyPermit == 'must') {
            return $this->dateComplexVerify();
        }
        // nullable/empty 参数值可以是Null和date
        if (is_null($this->paramValue)) {
            return true;
        }
        return $this->dateComplexVerify();
    }

    /**
     * 文件校验
     *
     * @return bool
     */
    protected function isFile(): bool
    {
        // 值必须是文件
        if ($this->verifyPermit == 'must') {
            return $this->fileComplexVerify();
        }
        // nullable/empty 参数值可以是Null和date
        if (is_null($this->paramValue)) {
            return true;
        }
        return $this->fileComplexVerify();
    }

    /**
     * 验证JSON字符串
     *
     * @return bool
     */
    protected function isJson(): bool
    {
        if ($this->verifyPermit == 'must') {
            return $this->jsonComplexVerify();
        }
        // nullable/empty 参数值可以是Null和json
        if (is_null($this->paramValue)) {
            return true;
        }
        return $this->jsonComplexVerify();
    }

    /**
     * 正则验证
     *  用于email url ip的校验
     *
     * @param array $regular
     * @return bool
     */
    protected function isRegular(array $regular): bool
    {
        if ($this->verifyPermit == 'must') {
            return $this->pregComplexVerify($regular);
        }
        // nullable/empty 参数值可以是Null和json
        if (is_null($this->paramValue)) {
            return true;
        }
        return $this->pregComplexVerify($regular);
    }

    /**
     * 数字类型的其他规则校验
     *
     * @param bool $isInt
     * @return bool
     */
    private function numberComplexVerify(bool $isInt = true): bool
    {
        // 必须是整数
        if ($isInt && !is_int($this->paramValue)) {
            return false;
        }
        // 必须是数字
        if (!$isInt && !is_numeric($this->paramValue)) {
            return false;
        }
        // in条件校验规则
        $inValue = array_filter($this->getVerifyInValue(), function ($item) use ($isInt) {
            return $isInt ? is_int($item) : is_numeric($item);
        });
        if (!empty($inValue) && !in_array($this->paramValue, $inValue, true)) {
            return false;
        }
        // preg
        $preg = $this->getVerifyPregValue();
        if (!is_null($preg) && !preg_match($preg, $this->paramValue)) {
            return false;
        }
        // min max length
        return $this->numberCompare($this->paramValue, false, $isInt);
    }

    /**
     * 字符串类型的其他规则校验
     *
     *
     * @return bool
     */
    private function stringComplexVerify(): bool
    {
        if (!is_string($this->paramValue)) {
            return false;
        }
        // in条件校验规则
        $inValue = array_filter($this->getVerifyInValue(), function ($item) {
            return is_string($item);
        });
        if (!empty($inValue) && !in_array($this->paramValue, $inValue, true)) {
            return false;
        }
        // preg
        $preg = $this->getVerifyPregValue();
        if (!is_null($preg) && !preg_match($preg, $this->paramValue)) {
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
        $inValue = array_filter($this->getVerifyInValue(), function ($item) use($format) {
            return $this->isDateValue($item, $format);
        });
        if (!empty($inValue) && !in_array($this->paramValue, $inValue, true)) {
            return false;
        }
        $timestamp = $this->getVerifyDateTimestamp($this->paramValue, $format);
        // min
        $min = $this->getVerifyDateValue('min', $format);
        if (!is_null($min) && $timestamp < $min) {
            return false;
        }
        // max
        $max = $this->getVerifyDateValue('max', $format);
        if (!is_null($min) && $timestamp > $max) {
            return false;
        }
        // length
        $length = $this->getVerifyIntValue('length', true);
        if (!is_null($length) && $length != strlen($this->paramValue)) {
            return false;
        }
        // preg
        $preg = $this->getVerifyPregValue();
        if (!is_null($preg) && !preg_match($preg, $this->paramValue)) {
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
            if (!is_uploaded_file($this->paramValue['tmp_name'][$key])) {
                return false;
            }
            // 上传发生错误
            if ($this->paramValue['error'][$key] > 0) {
                return false;
            }
            // MIME校验
            $fileMime = mime_content_type($this->paramValue['tmp_name'][$key]);
            $mine = $this->getVerifyMimeValue();
            if (!empty($mine) && !in_array($fileMime, $mine)) {
                return false;
            }
            // 上传文件最小size
            $min = $this->getVerifyIntValue('min', true);
            if (!is_null($min) && $this->paramValue['size'][$key] < $min) {
                return false;
            }
            // 上传文件最大size
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
    private function pregComplexVerify(array $regular): bool
    {
        // preg
        $preg = $this->getVerifyPregValue();
        if (is_null($preg)) {
            $pattern = $regular;
        } else {
            $pattern = [$preg];
        }
        if (!$this->isPregValue($pattern, $this->paramValue)) {
            return false;
        }
        // in条件校验规则
        $inValue = array_filter($this->getVerifyInValue(), function ($item) use ($pattern) {
            return $this->isPregValue($pattern, $item);
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
        $min = $isInt ? $this->getVerifyIntValue('min', $isPositive) : $this->getVerifyNumericValue('min');
        if (!is_null($min) && $number < $min) {
            return false;
        }
        // max
        $max = $isInt ? $this->getVerifyIntValue('max', $isPositive) : $this->getVerifyNumericValue('max');
        if (!is_null($max) && $number > $max) {
            return false;
        }
        // length
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
     * 获取校验规则中preg的值
     *
     * @return string|null
     */
    private function getVerifyPregValue(): ?string
    {
        if (array_key_exists('preg', $this->verifyRule) && (is_string($this->verifyRule['preg']))) {
            return $this->verifyRule['preg'];
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
        return $date && $date->format($format) == $date;
    }

    /**
     * 校验正则参数
     *
     * @param array $pattern
     * @param string $value
     *
     * @return bool
     */
    private function isPregValue(array $pattern, string $value): bool
    {
        foreach ($pattern as $preg) {
            if (preg_match($preg, $value)){
                return true;
            }
        }
        return false;
    }
}
