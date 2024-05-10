<?php

namespace Isobaric\Utils\Helper;

abstract class VerifyHelper extends RequestHelper
{
    /**
     * 邮箱的默认校验规则
     *
     * @var string[]
     */
    private array $emailDefaultRegex = [
        '/^([a-z0-9_.-]+)@([\da-z.-]+)\.([a-z.]{2,6})$/',
        '/^[a-z\d]+(\.[a-z\d]+)*@([\da-z](-[\da-z])?)+(\.{1,2}[a-z]+)+$/'
    ];

    /**
     * url的默认校验规则
     *
     * @var string[]
     */
    private array $urlDefaultRegex = [
        '/^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w.-]*)*\/?$/'
    ];

    /**
     * IP的默认校验规则
     *
     * @var string[]
     */
    private array $ipDefaultRegex = [
        '/((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)/',
        '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$'
    ];

    // 校验方法
    private string $stringVerify = 'stringComplexVerify';

    //
    private string $dateVerify = 'dateComplexVerify';

    //
    private string $fileVerify = 'fileComplexVerify';

    //
    private string $regexVerify = 'regexComplexVerify';

    //
    private string $jsonVerify = 'jsonComplexVerify';

    //
    private string $numberVerify = 'numberComplexVerify';

    /**
     * 校验参数
     *
     * @param array $params
     * @param array $rule
     * @param string $field
     *
     * @return bool
     */
    protected function paramFilter(array $params, array $rule, string $field): bool
    {
        $this->verifyFactor = null;

        $this->verifyParams = $params;
        // 当前校验规则
        $this->verifyRule = $rule;
        // 当前校验的字段名
        $this->paramKey = $field;
        // 字段值要求校验
        if (!$this->fieldInitialize()) {
            return true;
        }
        // 参数值校验
        return $this->verifyParamsValue();
    }

    /**
     * 初始化校验字段
     *
     * @return bool
     */
    protected function fieldInitialize(): bool
    {
        // 没有指定参数类型 或者 参数类型不在指定的范围内，则不校验
        if (!isset($this->verifyRule['type']) || !in_array($this->verifyRule['type'], $this->defaultType, true)) {
            return false;
        }
        // 校验字段要求
        $this->verifyPermit = $this->verifyRule['permit'] ?? 'must';
        if (!in_array($this->verifyPermit, $this->defaultPermit, true)) {
            return false;
        }
        // 当前校验的参数的类型
        $this->verifyType = $this->verifyRule['type'];
        // 当前校验的条件
        $this->verifyFactor = $this->verifyType;
        // 当前校验的参数的值
        $this->paramValue = $this->verifyParams[$this->paramKey] ?? null;
        // 当前校验的参数别名
        $this->verifyAlias = $this->verifyRule['alias'] ?? $this->paramKey;
        return true;
    }

    /**
     * 参数值校验
     *
     * @return bool
     */
    protected function verifyParamsValue(): bool
    {
        return match ($this->verifyType) {
            'bool' => $this->isBool(),
            'int' => $this->multipleVerify($this->numberVerify, true),
            'numeric' => $this->multipleVerify($this->numberVerify, false),
            'string' => $this->multipleVerify($this->stringVerify),
            'array' => $this->isArray(),
            'list' => $this->isList(),
            'date' => $this->multipleVerify($this->dateVerify),
            'file' => $this->multipleVerify($this->fileVerify),
            'email' => $this->multipleVerify($this->regexVerify, $this->emailDefaultRegex),
            'json' => $this->multipleVerify($this->jsonVerify),
            'url' => $this->multipleVerify($this->regexVerify, $this->urlDefaultRegex),
            'ip' => $this->multipleVerify($this->regexVerify, $this->ipDefaultRegex),
            default => true,
        };
    }

    /**
     * 参数校验失败的错误说明 todo
     *
     * @return string
     */
    protected function getFalseMessage(): string
    {
        if ($this->paramValue === null) {
            return $this->verifyAlias . '不能为空';
        }

        $message = '';
        switch ($this->verifyType) {
            case 'bool':
                $message = $this->getGeneralMessage();
                break;
            case 'int':
            case 'numeric':
                $message = $this->getNumberMessage();
                break;
            case 'string':
            case 'email':
            case 'json':
            case 'url':
            case 'ip':
                $message = $this->getStringMessage();
            break;
            case 'array':
            case 'list':
                $message = $this->getArrayMessage();
            break;
            case 'date':
                $message = $this->getDateMessage();
                break;
            case 'file':
                $message = $this->getFileMessage();
        }
        return $this->verifyAlias . $message;
    }

    /**
     * 数字提示
     *
     * @return string
     */
    private function getNumberMessage(): string
    {
        return match ($this->verifyFactor) {
            'min' => '不能小于' . $this->verifyRule['min'],
            'max' => '不能大于' . $this->verifyRule['max'],
            default => $this->getGeneralMessage(),
        };
    }

    /**
     * 字符串提示
     *
     * @return string
     */
    private function getStringMessage(): string
    {
        return match ($this->verifyFactor) {
            'min' => '长度不能小于' . $this->verifyRule['min'],
            'max' => '长度不能大于' . $this->verifyRule['max'],
            default => $this->getGeneralMessage(),
        };
    }

    /**
     * 数组提示
     *
     * @return string
     */
    private function getArrayMessage(): string
    {
        return match ($this->verifyFactor) {
            'min' => '元素数量不能小于' . $this->verifyRule['min'],
            'max' => '元素数量不能大于' . $this->verifyRule['max'],
            default => $this->getGeneralMessage(),
        };
    }

    /**
     * 日期提示
     *
     * @return string
     */
    private function getDateMessage(): string
    {
        return match ($this->verifyFactor) {
            'min' => '应该是' . $this->verifyRule['min'] . '及以后的日期',
            'max' => '应该是' . $this->verifyRule['max'] . '及以前的日期',
            default => $this->getGeneralMessage(),
        };
    }

    /**
     * 文件提示
     *
     * @return string
     */
    private function getFileMessage(): string
    {
        return match ($this->verifyFactor) {
            'min' => '不能小于' . $this->verifyRule['min'] . 'KB',
            'max' => '不能超过' . $this->verifyRule['max'] . 'KB',
            default => $this->getGeneralMessage(),
        };
    }

    /**
     * 通用提示
     *
     * @return string
     */
    private function getGeneralMessage(): string
    {
        if ($this->verifyFactor == 'length') {
            if (in_array($this->verifyType, ['array', 'list'])) {
                return '元素数量必须是' . $this->verifyRule['length'];
            } else {
                return '长度必须是' . $this->verifyRule['length'];
            }
        }
        if ($this->verifyFactor == 'in') {
            return '无效';
        }
        return '格式错误';
    }
}
