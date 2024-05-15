<?php

namespace Isobaric\Utils;

class ArrayUtil
{
    /**
     * 判断$array中下标为$key的值是否为空字符串或null
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isEmptyString(array $array, string|int $key): bool
    {
        if (!array_key_exists($key, $array)) {
            return true;
        }

        if (is_null($array[$key])) {
            return true;
        }

        if (!is_string($array[$key]) && !is_numeric($array[$key])) {
            return false;
        }

        return trim($array[$key]) === '';
    }

    /**
     * 判断$array中下标为$key的值是否不是空字符串或null
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isNotEmptyString(array $array, string|int $key): bool
    {
        if (!array_key_exists($key, $array) || (!is_string($array[$key]) && !is_numeric($array[$key]))) {
            return false;
        }

        return trim($array[$key]) !== '';
    }

    /**
     * 数组中的某个值是否为空
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isValEmpty(array $array, string|int $key): bool
    {
        if (!array_key_exists($key, $array)) {
            return true;
        }

        return empty($array[$key]);
    }

    /**
     * 数组中的某个值是否不为空
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isValNotEmpty(array $array, string|int $key): bool
    {
        return !self::isValEmpty($array, $key);
    }

    /**
     * 数组中的某个值是否为整数
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isValInt(array $array, string|int $key): bool
    {
        if (!array_key_exists($key, $array)) {
            return false;
        }
        return NumericUtil::isInt($array[$key]);
    }

    /**
     * 数组中的某个值是否为正整数
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isValPosInt(array $array, string|int $key): bool
    {
        if (!array_key_exists($key, $array)) {
            return false;
        }
        return NumericUtil::isPosInt($array[$key]);
    }

    /**
     * 数组中的某个值是否为自然数
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isValNatInt(array $array, string|int $key): bool
    {
        if (!array_key_exists($key, $array)) {
            return false;
        }
        return NumericUtil::isNatInt($array[$key]);
    }

    /**
     * 数组中的某个值是否为数组
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isValArray(array $array, string|int $key): bool
    {
        if (!array_key_exists($key, $array)) {
            return false;
        }
        return is_array($array[$key]);
    }

    /**
     * 返回以$key字段为key的三维数组
     *
     * @param array $array  二维数组
     * @param string $key   数组中的下标
     * @return array
     */
    public static function groupBy(array $array, string $key): array
    {
        $result = [];
        foreach ($array as $value) {
            $result[$value[$key]][] = $value;
        }
        return $result;
    }

    /**
     * 返回以$key字段为key的二维数组
     *
     * @param array $array  二维数组
     * @param string $key   数组中的下标
     * @return array
     */
    public static function keyBy(array $array, string $key): array
    {
        return array_column($array, null, $key);
    }

    /**
     * 三维数组排序 - 正序（以$array中$key的值正叙排列）
     *
     * @param array $array
     * @param string $key
     * @return void
     */
    public static function usort(array &$array, string $key): void
    {
        usort($array, function ($a, $b) use ($key) {
            return $a[$key] <=> $b[$key];
        });
    }

    /**
     * 三维数组排序 - 正序（以$array中$key的值正叙排列，返回值保持索引和值的对应关系）
     *
     * @param array $array
     * @param string $key
     * @return void
     */
    public static function uasort(array &$array, string $key): void
    {
        uasort($array, function ($a, $b) use ($key) {
            return $a[$key] <=> $b[$key];
        });
    }

    /**
     * 驼峰（大驼峰或小驼峰）格式的下标转换为下划线格式，int下标不处理
     *
     * @param array $array
     * @return array
     */
    public static function camelIndexToUnderline(array $array): array
    {
        foreach ($array as $index => $item) {
            unset($array[$index]);
            if (!is_int($index)) {
                $index = StringUtil::camelToUnderline($index);
            }
            $array[$index] = is_array($item) ? self::camelIndexToUnderline($item) : $item;
        }
        return $array;
    }
}
