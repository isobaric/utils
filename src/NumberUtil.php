<?php

namespace Isobaric\Utils;

class NumberUtil
{
    /**
     * 是否为整数；注意：多个0时返回true，即 000 或 '000' 都是整数
     *
     * @param $number
     * @return bool
     */
    public static function isInt($number): bool
    {
        if (is_float($number) || !is_numeric($number)) {
            return false;
        }

        return (bool)preg_match('/^-?\d+$/', $number);
    }

    /**
     * 是否为自然数，即非负整数
     *
     * @param $number
     * @return bool
     */
    public static function isNatInt($number): bool
    {
        return self::isInt($number) && $number >= 0;
    }

    /**
     * 是否为正整数
     *
     * @param $number
     * @return bool
     */
    public static function isPosInt($number): bool
    {
        return self::isInt($number) && $number > 0;
    }

    /**
     * 是否为负整数
     *
     * @param $number
     * @return bool
     */
    public static function isNegInt($number): bool
    {
        return self::isInt($number) && $number < 0;
    }

    /**
     * 是否为小数；注意：0.0000 == 0.0 或 '0.0000' == 0.0
     *
     * @param $number
     * @return bool
     */
    public static function isDecimal($number): bool
    {
        if (!is_float($number) && !is_numeric($number)) {
            return false;
        }
        return preg_match('/^-?\d+\.\d+$/', $number) || $number === 0.0;
    }

    /**
     * 是否为大于等于小数；>=0.0
     *
     * @param $number
     * @return bool
     */
    public static function isNatDecimal($number): bool
    {
        return self::isDecimal($number) && $number >= 0;
    }

    /**
     * 是否为大于0的小数；>=0.0...1
     *
     * @param $number
     * @return bool
     */
    public static function isPosDecimal($number): bool
    {
        return self::isDecimal($number) && $number > 0;
    }

    /**
     * 是否为小于0的小数；< 0.0
     *
     * @param $number
     * @return bool
     */
    public static function isNegDecimal($number): bool
    {
        return self::isDecimal($number) && $number < 0;
    }

    /**
     * 是否为限定小数位的小数
     *  不支持float类型零的长度限制：0.0 / 0.000 等；此时限制长度无效
     *  应使用string类型的零限制长度：'0.00' / '0.000' 等
     * @param $number
     * @param int $min
     * @param int|null $max
     * @return bool
     */
    public static function isLimitDecimal($number, int $min, int $max = null): bool
    {
        if (is_null($max)) {
            $limit = '{' . $min . '}';
        } else {
            $limit = '{' . $min . ',' . $max . '}';
        }
        return self::isDecimal($number) && (preg_match('/^-?\d+\.\d' . $limit . '$/', $number) || $number === 0.0);
    }
}
