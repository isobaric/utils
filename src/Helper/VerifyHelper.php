<?php

namespace Horseloft\Utils\Helper;

abstract class VerifyHelper extends RequestHelper
{
    /**
     * 邮箱的默认校验规则
     *
     * @var string[]
     */
    private $emailDefaultPattern = [
        '/^([a-z0-9_.-]+)@([\da-z.-]+)\.([a-z.]{2,6})$/',
        '/^[a-z\d]+(\.[a-z\d]+)*@([\da-z](-[\da-z])?)+(\.{1,2}[a-z]+)+$/'
    ];

    /**
     * url的默认校验规则
     *
     * @var string[]
     */
    private $urlDefaultPattern = [
        '/^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w.-]*)*\/?$/'
    ];

    /**
     * IP的默认校验规则
     *
     * @var string[]
     */
    private $ipDefaultPattern = [
        '/((2[0-4]\d|25[0-5]|[01]?\d\d?)\.){3}(2[0-4]\d|25[0-5]|[01]?\d\d?)/',
        '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$'
    ];

    /**
     * 校验字段要求
     *
     * @param $field
     *
     * @return bool
     */
    protected function verifyPermit($field): bool
    {
        // 校验字段要求
        $permit = $this->verifyRule['permit'] ?? 'must';
        if (!in_array($permit, $this->defaultPermit, true)) {
            return true;
        }
        // 当前校验的参数的值
        $this->paramValue = $this->verifyParams[$field] ?? null;
        // 当前校验的错误提示语
        $this->message = $this->verifyRule['message'] ?? '验证失败';
        // 必传参数和值，值不能为Null
        if ($permit == 'must' && is_null($this->paramValue)) {
            return false;
        }
        // 必传参数，值非必传
        if ($permit == 'nullable' && !array_key_exists($field, $this->verifyParams)) {
            return false;
        }
        $this->verifyPermit = $permit;
        return true;
    }

    /**
     * 参数类型校验
     *
     * @return bool
     */
    protected function verifyType(): bool
    {
        // 没有指定参数类型 或者 参数类型不在指定的范围内，则不校验
        if (!isset($this->verifyRule['type']) || !in_array($this->verifyRule['type'], $this->defaultType, true)) {
            return false;
        }
        $this->verifyType = $this->verifyRule['type'];
        return true;
    }

    /**
     * 参数值校验
     *
     * @return bool
     */
    protected function verifyParamsValue(): bool
    {
        switch ($this->verifyType) {
            case 'bool':
                return $this->isBool();
            case 'int':
                return $this->isInt();
            case 'numeric':
                return $this->isNumeric();
            case 'string':
                return $this->isString();
            case 'array':
                return $this->isArray();
            case 'list':
                return $this->isList();
            case 'date':
                return $this->isDate();
            case 'file':
               return $this->isFile();
            case 'email':
                return $this->isRegular($this->emailDefaultPattern);
            case 'json':
                return $this->isJson();
            case 'url':
                return $this->isRegular($this->urlDefaultPattern);
            case 'ip':
                return $this->isRegular($this->ipDefaultPattern);
            default:
                return true;
        }
    }
}
