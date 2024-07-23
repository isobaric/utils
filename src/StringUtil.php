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
     * @param string|null $camelCaseStr
     * @return string
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

    /**
     * 符号分割的字符串转为数组
     *
     * @param string|null $string
     * @param string $separator
     * @return array
     */
    public static function separatorToList(?string $string, string $separator = ','): array
    {
        if (strlen($string) == 0) {
            return [];
        }
        return explode($separator, trim($string, $separator));
    }

    /**
     * 符号分割的字符串转为int数组
     *
     * @param string|null $string
     * @param string $separator
     * @return array
     */
    public static function separatorToIntList(?string $string, string $separator = ','): array
    {
        return array_map('intval', self::separatorToList($string, $separator));
    }

    /**
     * 符号分割的字符串转为不重复的int数组
     *
     * @param string|null $string
     * @param string $separator
     * @return array
     */
    public static function separatorToUniqueList(?string $string, string $separator = ','): array
    {
        $list = self::separatorToList($string, $separator);
        if (empty($list)) {
            return [];
        }
        return array_values(array_unique($list));
    }

    /**
     * 符号分割的字符串转为过滤空之后的不重复的int数组
     *
     * @param string|null $string
     * @param string $separator
     * @return array
     */
    public static function separatorToFilterList(?string $string, string $separator = ','): array
    {
        $list = self::separatorToList($string, $separator);
        if (empty($list)) {
            return [];
        }
        return array_values(array_filter(array_unique($list)));
    }

    /**
     * 验证字符串是否是中文字符串
     *
     * @param string|null $string $string   字符串
     * @param int|null $min                 字符串的最小长度
     * @param int|null $max                 字符串的最大长度
     * @return bool
     */
    public static function isCn(?string $string, ?int $min = null, ?int $max = null): bool
    {
        $limit = '+';
        if (!is_null($min)) {
            $limit = $min;

            if (!is_null($max)) {
                $limit .= ',' . $max;
            }
            $limit = '{' . $limit . '}';
        }

        $reg = '/^[\x{4e00}-\x{9fa5}]' . $limit . '$/u';

        if (preg_match($reg, $string)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 是否是中文英文字符串
     *
     * @param string $string
     * @param int $min
     * @param int $max
     * @return bool
     */
    public static function isEnCn(string $string, int $min = 0, int $max = 3): bool
    {
        if (preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z]{' . $min . ',' . $max . '}$/u', $string)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 校验中文+英文+数字
     *
     * @param string $string
     * @param int $min
     * @param int $max
     * @return bool
     */
    public static function isEnCnNumber(string $string, int $min = 0, int $max = 3): bool
    {
        if (preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z\d]{' . $min . ',' . $max . '}$/u', $string)) {
            return true;
        } else {
            return false;
        }
    }
}
