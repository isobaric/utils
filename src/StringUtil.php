<?php

namespace Isobaric\Utils;

class StringUtil
{
    /**
     * 验证字符串是否是邮箱
     * @param string|null $email
     * @return bool
     */
    public static function isEmail(?string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 将驼峰（大驼峰或小驼峰）格式的字符串转换为下划线格式
     *
     * @param string|null $camelCaseStr 驼峰格式的字符串（大驼峰或小驼峰）
     * @return string 下划线格式的字符串
     */
    public static function camelToUnderline(?string $camelCaseStr): string
    {
        $underlineStr = preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $camelCaseStr);
        return strtolower($underlineStr);
    }

    /**
     * 下滑线分割的字符串转为小驼峰字符串
     *
     * @param string|null $underlineString
     * @return string
     */
    public static function underlineToCamel(?string $underlineString): string
    {
        return preg_replace_callback('/_([a-z])/', function($matches) {
            return strtoupper($matches[1]);
        }, $underlineString);
    }
}
