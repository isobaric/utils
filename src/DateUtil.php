<?php

namespace Isobaric\Utils;

class DateUtil
{
    /**
     * 日期格式转换
     *
     *  例：
     *  Y-m-d H:i:s 转 Y-m-d
     *  Y-m-d H:i:s 转 H:i:s
     *
     * @param string|null $date
     * @param string $format
     * @return string
     */
    public static function dateFormat(?string $date, string $format = 'Y-m-d'): string
    {
        if ($date == '') {
            return '';
        }
        $dateTime = date_create($date);
        if (!$dateTime) {
            return '';
        }
        $result = date_format($dateTime, $format);
        if (!$result) {
            return '';
        }
        return $result;
    }

    /**
     * 日期格式转换
     *
     *  例：
     *  Y/m/d H:i:s 转 Y-m-d H:i:s
     *
     * @param string|null $date
     * @return string
     */
    public static function datetimeFormat(?string $date): string
    {
        return self::dateFormat($date, 'Y-m-d H:i:s');
    }

    /**
     * 日期格式校验
     *
     * @param string|null $date
     * @param string $format
     *
     * @return bool
     */
    public static function isDate(?string $date, string $format = 'Y-m-d'): bool
    {
        if ($date == '') {
            return false;
        }
        $result = self::dateFormat($date, $format);
        return $result && $result == $date;
    }

    /**
     * 日期格式校验
     *
     * @param string|null $date
     *
     * @return bool
     */
    public static function isDatetime(?string $date): bool
    {
        return self::isDate($date, 'Y-m-d H:i:s');
    }

    /**
     * 是否是未来日期
     *
     * @param string|null $date
     * @param string $format
     *
     * @return bool
     */
    public static function isFutureDatetime(?string $date, string $format = 'Y-m-d H:i:s'): bool
    {
        if (!self::isDatetime($date, $format)) {
            return false;
        }
        if (self::getTimestamp($date, $format) > time()) {
            return true;
        }
        return false;
    }

    /**
     * 日期比较
     *  转时间戳之后对比
     *
     * @param string $date
     * @param string $nextDate
     * @param string $compare
     *
     * @return bool
     */
    public static function compare(string $date, string $nextDate, string $compare = '>'): bool
    {
        $compare = trim($compare);
        return eval("return \"strtotime($date)\" $compare \"strtotime($nextDate)\";");
    }

    /**
     * 日期转时间戳
     *
     * @param string $date
     * @param string $format
     *
     * @return int
     */
    public static function getTimestamp(string $date, string $format = 'Y-m-d H:i:s'): int
    {
        $date = \DateTime::createFromFormat($format, $date);
        return $date->getTimestamp();
    }

    /**
     * 计算时间差 返回值：秒|失败时返回false
     *
     * @param string|null $date
     * @param string|null $nextDate
     *
     * @return false|int
     */
    public static function dateSubtraction(?string $date, ?string $nextDate): bool|int
    {
        if ($date == '' || $nextDate == '') {
            return false;
        }
        return strtotime($date) - strtotime($nextDate);
    }

    // 获取当前的 年 月 日 时 分 秒 周 | 年 月 日 时 分 秒 周的第几天 | 两个日期的 年月日时分秒 差值 | 判断 今天 明天 昨天 TODO
}
