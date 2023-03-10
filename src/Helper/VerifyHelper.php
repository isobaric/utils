<?php

namespace Horseloft\Utils\Helper;

abstract class VerifyHelper extends RequestHelper
{
    /**
     * 邮箱的默认校验规则
     *
     * @var string[]
     */
    private $emailDefaultRegex = [
        '/^([a-z0-9_.-]+)@([\da-z.-]+)\.([a-z.]{2,6})$/',
        '/^[a-z\d]+(\.[a-z\d]+)*@([\da-z](-[\da-z])?)+(\.{1,2}[a-z]+)+$/'
    ];

    /**
     * url的默认校验规则
     *
     * @var string[]
     */
    private $urlDefaultRegex = [
        '/^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w.-]*)*\/?$/'
    ];

    /**
     * IP的默认校验规则
     *
     * @var string[]
     */
    private $ipDefaultRegex = [
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
        $this->verifyPermit = $this->verifyRule['permit'] ?? 'must';
        if (!in_array($this->verifyPermit, $this->defaultPermit, true)) {
            return true;
        }
        // 当前校验的参数的值
        $this->paramValue = $this->verifyParams[$field] ?? null;
        // 当前校验的参数别名
        $this->verifyAlias = $this->verifyRule['alias'] ?? $field;
        // 必传参数和值，值不能为Null
        if ($this->verifyPermit == 'must' && is_null($this->paramValue)) {
            return false;
        }
        // 必传参数，值非必传
        if ($this->verifyPermit == 'nullable' && !array_key_exists($field, $this->verifyParams)) {
            return false;
        }
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
                return $this->isRegular($this->emailDefaultRegex);
            case 'json':
                return $this->isJson();
            case 'url':
                return $this->isRegular($this->urlDefaultRegex);
            case 'ip':
                return $this->isRegular($this->ipDefaultRegex);
            default:
                return true;
        }
    }

    /**
     * 参数校验失败的错误说明
     *
     * @return string
     */
    protected function getFalseMessage(): string
    {
        $message = '';
        switch ($this->verifyType) {
            case 'bool':
                $message .= '格式错误';
                break;
            case 'int':
            case 'numeric':
                switch ($this->verifyFactor) {
                    case 'min':
                        $message .= '不能小于' . $this->verifyRule['min'];
                        break;
                    case 'max':
                        $message .= '不能大于' . $this->verifyRule['max'];
                        break;
                    case 'int':
                        $message .= '必须是整数';
                        break;
                    case 'number':
                        $message .= '必须是数字';
                        break;
                }
                break;
            case 'string':
            case 'email':
            case 'json':
            case 'url':
            case 'ip':
                switch ($this->verifyFactor) {
                    case 'min':
                        $message .= '长度不能小于' . $this->verifyRule['min'];
                        break;
                    case 'max':
                        $message .= '长度不能大于' . $this->verifyRule['max'];
                        break;
                    case 'string':
                        $message .= '必须是字符串';
                        break;
                }
                break;
            case 'array':
            case 'list':
                switch ($this->verifyFactor) {
                    case 'min':
                        $message .= '元素数量不能小于' . $this->verifyRule['min'];
                        break;
                    case 'max':
                        $message .= '元素数量不能大于' . $this->verifyRule['max'];
                        break;
                }
                break;
            case 'date':
                switch ($this->verifyFactor) {
                    case 'min':
                        $message .= '应该是' . $this->verifyRule['min'] . '及以后的日期';
                        break;
                    case 'max':
                        $message .= '应该是' . $this->verifyRule['max'] . '及以前的日期';
                        break;
                }
                break;
            case 'file':
                switch ($this->verifyFactor) {
                    case 'min':
                        $message .= '不能小于' . $this->verifyRule['min'] . 'KB';
                        break;
                    case 'max':
                        $message .= '不能超过' . $this->verifyRule['max'] . 'KB';
                        break;
                }
                break;
        }
        switch ($this->verifyFactor) {
            case 'in':
            case 'date':
            case 'mime':
                $message .= '无效';
                break;
            case 'regex':
                $message .= '格式错误';
                break;
            case 'length':
                if (in_array($this->verifyType, ['array', 'list'])) {
                    $message .= '元素数量必须是' . $this->verifyRule['length'];
                } else {
                    $message .= '长度必须是' . $this->verifyRule['length'];
                }
                break;
        }
        if ($message == '') {
            switch ($this->verifyPermit) {
                case 'must':
                    $message .= '不能为空';
                    break;
                case 'nullable':
                    $message .= '必须存在';
                    break;
            }
        }
        return $this->verifyAlias . $message;
    }
}
