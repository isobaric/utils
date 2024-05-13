<?php

namespace Isobaric\Utils;

class NumericUtil
{
    /**
     * 是否为整数；注意：多个0时返回true，即 000 或 '000' 都是整数
     *
     * @param $numeric
     * @return bool
     */
    public static function isInt($numeric): bool
    {
        if (is_float($numeric) || !is_numeric($numeric)) {
            return false;
        }

        return (bool)preg_match('/^-?\d+$/', $numeric);
    }

    /**
     * 是否为自然数，即非负整数
     *
     * @param $numeric
     * @return bool
     */
    public static function isNatInt($numeric): bool
    {
        return self::isInt($numeric) && $numeric >= 0;
    }

    /**
     * 是否为正整数
     *
     * @param $numeric
     * @return bool
     */
    public static function isPosInt($numeric): bool
    {
        return self::isInt($numeric) && $numeric > 0;
    }

    /**
     * 是否为负整数
     *
     * @param $numeric
     * @return bool
     */
    public static function isNegInt($numeric): bool
    {
        return self::isInt($numeric) && $numeric < 0;
    }

    /**
     * 是否为小数；注意：0.0000 == 0.0 或 '0.0000' == 0.0
     *
     * @param $numeric
     * @return bool
     */
    public static function isDecimal($numeric): bool
    {
        if (!is_float($numeric) && !is_numeric($numeric)) {
            return false;
        }
        return preg_match('/^-?\d+\.\d+$/', $numeric) || $numeric === 0.0;
    }

    /**
     * 是否为大于等于小数；>=0.0
     *
     * @param $numeric
     * @return bool
     */
    public static function isNatDecimal($numeric): bool
    {
        return self::isDecimal($numeric) && $numeric >= 0;
    }

    /**
     * 是否为大于0的小数；>=0.0...1
     *
     * @param $numeric
     * @return bool
     */
    public static function isPosDecimal($numeric): bool
    {
        return self::isDecimal($numeric) && $numeric > 0;
    }

    /**
     * 是否为小于0的小数；< 0.0
     *
     * @param $numeric
     * @return bool
     */
    public static function isNegDecimal($numeric): bool
    {
        return self::isDecimal($numeric) && $numeric < 0;
    }

    /**
     * 是否为限定小数位的小数
     *  不支持float类型零的长度限制：0.0 / 0.000 等；此时限制长度无效
     *  应使用string类型的零限制长度：'0.00' / '0.000' 等
     * @param $numeric
     * @param int $min
     * @param int|null $max
     * @return bool
     */
    public static function isLimitDecimal($numeric, int $min, int $max = null): bool
    {
        if (is_null($max)) {
            $limit = '{' . $min . '}';
        } else {
            $limit = '{' . $min . ',' . $max . '}';
        }
        return self::isDecimal($numeric) && (preg_match('/^-?\d+\.\d' . $limit . '$/', $numeric) || $numeric === 0.0);
    }
}
