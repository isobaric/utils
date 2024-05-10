<?php

namespace Isobaric\Utils;

class DateUtil
{
    /**
     * 获取$date的DateTime对象
     *
     * @param string|null $date     日期或日期表达式；$date为空返回false
     * @param string|null $format   $date的格式；如果$format不是null则会对比$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return \DateTime|false      成功返回\DateTime对象，失败返回false
     * @example
     *  DateUtil::getDateTime('2026-02-01');
     *  DateUtil::getDateTime('2026-02-01 12:12', 'Y-m-d H:i');
     */
    public static function getDateTime(?string $date, ?string $format = 'Y-m-d'): \DateTime|false
    {
        if ($date == '') {
            return false;
        }
        try {
            $dateTime = date_create($date);
            if (!$dateTime) {
                return false;
            }

            if (is_null($format)) {
                return $dateTime;
            }

            $result = date_format($dateTime, $format);
            if (!$result) {
                return false;
            }

            if ($result != $date) {
                return false;
            }

            return $dateTime;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 获取格式化后的日期
     *
     * @param string|null $date 日期或日期表达式；$date为空返回空字符串
     * @param string $format    返回值格式
     * @return string   成功返回$format格式的日期，失败返回空字符串
     * @example
     *  DateUtil::format('+1 day');
     *  DateUtil::format('2024-03-01 12:30');
     *  DateUtil::format('2024-03-01 12:30:10', 'H:i:s');
     */
    public static function format(?string $date, string $format = 'Y-m-d'): string
    {
        if ($date == '') {
            return '';
        }
        try {
            $dateTime = date_create($date);
            if (!$dateTime) {
                return '';
            }

            $result = date_format($dateTime, $format);
            if (!$result) {
                return '';
            }
            return $result;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 获取格式化的日期
     *
     * @param string $format            返回值的日期格式
     * @param string|null $date         日期或日期表达式
     * @param string|null $dateFormat   $date的日期格式
     * @return string
     *  成功返回格式化的日期字符串
     *      如果$date是null，$dateFormat不是null则返回空字符串；
     *      如果$date是null，$dateFormat也是null则返回format后的当前日期；
     *      如果$date不是null，$dateFormat是null则不验证$date的日期格式，返回format后的日期；
     *      如果$date不是null，$dateFormat不是null则会比较$date是否与格式化之后的日期相等，如果相等返回format后的日期；
     *  失败返回空字符串
     * @example
     *  DateUtil::formatDate('Y-m-d');
     *  DateUtil::formatDate('Y-m-d', '+ 1 day');
     *  DateUtil::formatDate('Y-m-d', '2023-02-01 00:12:00', 'Y-m-d H:i:s');
     */
    public static function formatDate(string $format, ?string $date = null, ?string $dateFormat = null): string
    {
        if ($format == '') {
            return '';
        }

        if (is_null($date)) {
            return !is_null($dateFormat) ? '' : date($format);
        }

        $dateTime = self::getDateTime($date, $dateFormat);
        if (!$dateTime) {
            return '';
        }
        return $dateTime->format($format);
    }

    /**
     * 获取Y-m-d H:i:s格式的日期
     *
     * @param string|null $date 日期或日期表达式
     * @return string   成功返回Y-m-d H:i:s格式的日期，失败返回空字符串
     * @example
     *  DateUtil::formatDatetime('2023/12/12 12:12:12');
     *  DateUtil::formatDatetime('-1 day');
     */
    public static function formatDatetime(?string $date): string
    {
        return self::format($date, 'Y-m-d H:i:s');
    }

    /**
     * 获取毫秒
     *
     * @return int
     */
    public static function millisecond(): int
    {
        return microtime(true) * 1000;
    }

    /**
     * 获取当前日期或指定日期的开始时间戳
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今天
     * @param string|null $format   $date的格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return false|int    成功返回日期的开始时间戳，失败返回false
     * @example
     *  DateUtil::dateStartTime();
     *  DateUtil::dateStartTime('+7 day');
     *  DateUtil::dateStartTime('2022-12-12');
     *  DateUtil::dateStartTime('2022-12-12', 'Y-m-d');
     *  DateUtil::dateStartTime('2030-12-31 12:12:12');
     *  DateUtil::dateStartTime('2030-12-31 12:12:12', 'Y-m-d H:i:s');
     */
    public static function dateStartTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-d', $date, $format);
        if ($day == '') {
            return false;
        }

        return strtotime($day);
    }

    /**
     * 获取当前日期或指定日期的开始时间
     *
     * @param string|null $date 日期或日期表达式，如果是null则默认日期为今天
     * @param string $format    返回值的格式
     * @return string   成功返回格式化后的日期开始时间，失败返回空字符串
     * @example
     *  DateUtil::dateStart();
     *  DateUtil::dateStart('2030-11-11');
     *  DateUtil::dateStart(null, 'Y-m-d H:i');
     *  DateUtil::dateStart('+1 day', 'Y-m-d H:i');
     */
    public static function dateStart(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::dateStartTime($date);
        return $time ? date($format, $time) : '';
    }

    /**
     * 获取当前日期或指定日期的结束时间戳
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今天
     * @param string|null $format   $date的格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return false|int    成功返回日期的结束时间戳，失败返回false
     * @example
     *  DateUtil::dateEndTime();
     *  DateUtil::dateEndTime('+7 day');
     *  DateUtil::dateEndTime('2022-12-12');
     *  DateUtil::dateEndTime('2022-12-12', 'Y-m-d');
     */
    public static function dateEndTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-d 23:59:59', $date, $format);
        if ($day == '') {
            return false;
        }

        return strtotime($day);
    }

    /**
     * 获取当前日期或指定日期的结束时间
     *
     * @param string|null $date 日期或日期表达式，如果是null则默认日期为今天
     * @param string $format    返回值的格式
     * @return string   成功返回格式化后的日期的结束时间，失败返回空字符串
     * @example
     *  DateUtil::dateEnd();
     *  DateUtil::dateEnd('+1 day');
     *  DateUtil::dateEnd('2030-11-11');
     *  DateUtil::dateEnd('2030-11-11', 'H:i Y/m/d');
     */
    public static function dateEnd(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::dateEndTime($date);
        return $time ? date($format, $time) : '';
    }

    /**
     * 获取本周或指定日期所在周的开始时间戳
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今天
     * @param string|null $format   $date的格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return false|int    成功返回周的开始时间戳，失败返回false
     * @example
     *  DateUtil::weekStartTime();
     *  DateUtil::weekStartTime('+3 day');
     *  DateUtil::weekStartTime('2030-05-10');
     *  DateUtil::weekStartTime('2030-05-10', 'Y-m-d');
     */
    public static function weekStartTime(?string $date = null, ?string $format = null): false|int
    {
        $formatDate = self::formatDate('Y-m-d', $date, $format);
        if ($formatDate == '') {
            return false;
        }

        $timestamp = strtotime($formatDate);
        $week = intval(date('N', $timestamp));
        $revise = ($week - 1) * 86400;

        return $timestamp - $revise;
    }

    /**
     * 获取本周或指定日期所在周的开始时间
     *
     * @param string|null $date 日期或日期表达式，如果是null则默认日期为今天
     * @param string $format    返回值的格式
     * @return string   成功返回格式化后周的开始时间，失败返回空字符串
     * @example
     *  DateUtil::weekStartTime();
     *  DateUtil::weekStartTime('+3 day');
     *  DateUtil::weekStartTime('2030-05-10');
     *  DateUtil::weekStart(null, 'Y-m-d H:i');
     *  DateUtil::weekStartTime('2030-05-10', 'Y-m-d');
     */
    public static function weekStart(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::weekStartTime($date);
        return $time ? date($format, $time) : '';
    }

    /**
     * 获取本周或指定日期所在周的结束时间戳
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今天
     * @param string|null $format   $date的格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return false|int    成功返回周的结束时间戳，失败返回false
     * @example
     *  DateUtil::weekEndTime();
     *  DateUtil::weekEndTime('+3 day');
     *  DateUtil::weekEndTime('2030-05-10');
     *  DateUtil::weekEndTime('2030-05-10', 'Y-m-d');
     */
    public static function weekEndTime(?string $date = null, ?string $format = null): false|int
    {
        $formatDate = self::formatDate('Y-m-d 23:59:59', $date, $format);
        if ($formatDate == '') {
            return false;
        }

        $timestamp = strtotime($formatDate);
        $week = intval(date('N', $timestamp));
        $revise = (7 - $week) * 86400;

        return $timestamp + $revise;
    }

    /**
     * 获取本周或指定日期所在周的结束时间
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今天
     * @param string $format        返回值的格式
     * @return string   成功返回格式化后周的结束时间，失败返回空字符串
     * @example
     *  DateUtil::weekEnd();
     *  DateUtil::weekEnd('+7 day');
     *  DateUtil::weekEnd('2024-05-22');
     *  DateUtil::weekEnd(null, 'Y-m-d H:i');
     *  DateUtil::weekEnd('2024-05-22 12:12', 'Y-m-d H:i');
     */
    public static function weekEnd(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::weekEndTime($date);
        return $time ? date($format, $time) : '';
    }

    /**
     * 获取本月或指定日期所在月的开始时间戳
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今天
     * @param string|null $format   $date的格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return false|int    成功返回月的开始时间戳，失败返回false
     * @example
     *  DateUtil::monthStartTime();
     *  DateUtil::monthStartTime('+3 day');
     *  DateUtil::monthStartTime('2030-05-10');
     *  DateUtil::monthStartTime('2030-05-10', 'Y-m-d');
     */
    public static function monthStartTime(?string $date = null, ?string $format = null): false|int
    {
        $formatDate = self::formatDate('Y-m-01', $date, $format);
        if ($formatDate == '') {
            return false;
        }

        return strtotime($formatDate);
    }

    /**
     * 获取本月或指定日期所在月份的开始时间
     *
     * @param string|null $date 日期或日期表达式，如果是null则默认日期为今天
     * @param string $format    返回值的格式
     * @return string   成功返回格式化后月分的开始时间，失败返回空字符串
     * @example
     *  DateUtil::monthStart();
     *  DateUtil::monthStart('+7 day');
     *  DateUtil::monthStart('2024-05-22');
     *  DateUtil::monthStart(null, 'Y-m-d H:i');
     *  DateUtil::monthStart('2024-05-22 12:12', 'Y-m-d H:i');
     */
    public static function monthStart(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::monthStartTime($date);
        return $time ? date($format, $time) : '';
    }

    /**
     * 获取本月或指定日期所在月的结束时间戳
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今天
     * @param string|null $format   $date的格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return false|int    成功返回月的结束时间戳，失败返回false
     * @example
     *  DateUtil::monthEndTime();
     *  DateUtil::monthEndTime('+3 day');
     *  DateUtil::monthEndTime('2030-05-10');
     *  DateUtil::monthEndTime('2030-05-10', 'Y-m-d');
     */
    public static function monthEndTime(?string $date = null, ?string $format = null): false|int
    {
        $formatDate = self::formatDate('Y-m-t 23:59:59', $date, $format);
        if ($formatDate == '') {
            return false;
        }
        return strtotime($formatDate);
    }

    /**
     * 获取本月或指定日期所在月份的结束时间
     *
     * @param string|null $date 日期或日期表达式，如果是null则默认日期为今天
     * @param string $format    返回值的格式
     * @return string   成功返回格式化后月分的结束时间，失败返回空字符串
     * @example
     *  DateUtil::monthEnd();
     *  DateUtil::monthEnd('+7 day');
     *  DateUtil::monthEnd('2024-05-22');
     *  DateUtil::monthEnd(null, 'Y-m-d H:i');
     *  DateUtil::monthEnd('2024-05-22 12:12', 'Y-m-d H:i');
     */
    public static function monthEnd(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::monthEndTime($date);
        return $time ? date($format, $time) : '';
    }

    /**
     * 获取本年或指定日期所在年的开始时间戳
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今年
     * @param string|null $format   $date的格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return false|int    成功返回年的开始时间戳，失败返回false
     * @example
     *  DateUtil::yearStartTime();
     *  DateUtil::yearStartTime('1970');
     *  DateUtil::yearStartTime('+1 year');
     *  DateUtil::yearStartTime('2000-01-01');
     *  DateUtil::yearStartTime('2000-01-01', 'Y-m-d');
     */
    public static function yearStartTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-01-01', $date, $format);
        if ($day == '') {
            return false;
        }
        return strtotime($day);
    }

    /**
     * 年的开始时间
     *
     * @param string|null $date 日期或日期表达式，如果是null则默认日期为今年
     * @param string $format    返回值的格式
     * @return string   成功返回格式化后年的开始时间，失败返回空字符串
     * @example
     *  DateUtil::yearStart();
     *  DateUtil::yearStart('+1 year');
     *  DateUtil::yearStart('2024-05-22');
     *  DateUtil::yearStart(null, 'Y-m-d H:i');
     *  DateUtil::yearStart('2024-05-22 12:12', 'Y-m-d H:i');
     */
    public static function yearStart(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::yearStartTime($date);
        return $time ? date($format, $time) : '';
    }

    /**
     * 获取本年或指定日期所在年的结束时间戳
     *
     * @param string|null $date     日期或日期表达式，如果是null则默认日期为今年
     * @param string|null $format   $date的格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     * @return false|int    成功返回年的结束时间戳，失败返回false
     * @example
     *  DateUtil::yearEndTime();
     *  DateUtil::yearEndTime('1970');
     *  DateUtil::yearEndTime('+1 year');
     *  DateUtil::yearEndTime('2000-01-01');
     *  DateUtil::yearEndTime('2000-01-01', 'Y-m-d');
     */
    public static function yearEndTime(?string $date = null, ?string $format = null): false|int
    {
        $formatDate = self::formatDate('Y-12-31 23:59:59', $date, $format);
        if ($formatDate == '') {
            return false;
        }
        return strtotime($formatDate);
    }

    /**
     * 年的结束时间
     *
     * @param string|null $date 日期或日期表达式，如果是null则默认日期为今年
     * @param string $format    返回值的格式
     * @return string   成功返回格式化后年的结束时间，失败返回空字符串
     * @example
     *  DateUtil::yearEnd();
     *  DateUtil::yearEnd('+1 year');
     *  DateUtil::yearEnd('2024-05-22');
     *  DateUtil::yearEnd(null, 'Y-m-d H:i');
     *  DateUtil::yearEnd('2024-05-22 12:12', 'Y-m-d H:i');
     */
    public static function yearEnd(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::yearEndTime($date);
        return $time ? date($format, $time) : '';
    }

    /**
     * 校验日期格式
     *
     * @param string|null $date 日期或日期表达式
     * @param string $format    期待的$date的日期格式
     * @return bool 成功返回true，失败返回false
     * @example
     *  DateUtil::isDate('2029-03-01');
     *  DateUtil::isDate('1999', 'Y');
     *  DateUtil::isDate('2023/02/01 12:12', 'Y/m/d H:i');
     */
    public static function isDate(?string $date, string $format = 'Y-m-d'): bool
    {
        if ($date == '') {
            return false;
        }
        return !is_bool(self::getDateTime($date, $format));
    }

    /**
     * 校验日期格式是否是"Y-m-d H:i:s"
     *
     * @param string|null $datetime 日期或日期表达式
     * @return bool 成功返回true，失败返回false
     * @example
     *  DateUtil::isDatetime('2029-03-01 12:12:12');
     */
    public static function isDatetime(?string $datetime): bool
    {
        return self::isDate($datetime, 'Y-m-d H:i:s');
    }

    /**
     * 两个日期是否为时间段（即：$date 小于 $nextDate）
     *
     * @param string|null $date     第一个日期
     * @param string|null $nextDate 第二个日期
     * @param string $format        $date 和 $nextDate 的日期格式
     * @return bool 成功返回true，失败返回false
     * @example
     *  DateUtil::isInterval('2024-01-11', '2024-02-01');
     *  DateUtil::isInterval('2024-01-11 12:12', '2024-01-11 13:13', 'Y-m-d H:i');
     */
    public static function isInterval(?string $date, ?string $nextDate, string $format = 'Y-m-d'): bool
    {
        $datetime = self::getDateTime($date, $format);
        if (!$datetime) {
            return false;
        }
        $nextDatetime = self::getDateTime($nextDate, $format);
        if (!$nextDatetime) {
            return false;
        }

        return ($nextDatetime->getTimestamp() - $datetime->getTimestamp()) > 0;
    }

    /**
     * 两个格式为"Y-m-d H:i:s"的日期是否为时间段（即：$datetime 小于 $nextDatetime）
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @return bool
     * @example
     *  DateUtil::isIntervalDatetime('2024-02-01 00:00:00', '2024-02-01 23:59:59');
     */
    public static function isIntervalDatetime(?string $datetime, ?string $nextDatetime): bool
    {
        return self::isInterval($datetime, $nextDatetime, 'Y-m-d H:i:s');
    }

    /**
     * 校验日期是否大于当前时间
     *
     * @param string|null $date 日期或日期表达式
     * @param string $format    $date的格式
     * @return bool 成功返回true，失败返回false
     * @example
     *  DateUtil::isFuture('2030-02-01');
     *  DateUtil::isFuture('2030-01-01 21:00', 'Y-m-d H:i');
     */
    public static function isFuture(?string $date, string $format = 'Y-m-d'): bool
    {
        $dateTime = self::getDateTime($date, $format);
        if (!$dateTime) {
            return false;
        }

        return $dateTime->getTimestamp() > time();
    }

    /**
     * 校验格式为"Y-m-d H:i:s"的日期是否大于当前时间
     *
     * @param string|null $datetime
     * @return bool
     * @example
     *  DateUtil::isFutureDatetime('2030-01-01 21:00:00');
     */
    public static function isFutureDatetime(?string $datetime): bool
    {
        return self::isFuture($datetime, 'Y-m-d H:i:s');
    }

    /**
     * 校验日期是否为今天的时间
     *
     * @param string|null $datetime
     * @param string $format        $date的格式
     * @return true
     * @example
     *  DateUtil::isToday('2024-02-02 12:12:12');
     *  DateUtil::isToday('2024-02-02', 'Y-m-d');
     */
    public static function isToday(?string $datetime, string $format = 'Y-m-d H:i:s'): bool
    {
        $dateTime = self::getDateTime($datetime, $format);
        if (!$dateTime) {
            return false;
        }
        $timestamp = $dateTime->getTimestamp();
        $todayStart = self::dateStartTime();

        return $timestamp >= $todayStart && $timestamp <= ($todayStart + 86400);
    }

    /**
     * 校验日期是否为昨天的时间
     *
     * @param string|null $datetime
     * @param string $format        $date的格式
     * @return true
     * @example
     *  DateUtil::isYesterday('2024-02-02 12:12:12');
     *  DateUtil::isYesterday('2024-02-02', 'Y-m-d');
     */
    public static function isYesterday(?string $datetime, string $format = 'Y-m-d H:i:s'): bool
    {
        $dateTime = self::getDateTime($datetime, $format);
        if (!$dateTime) {
            return false;
        }
        $timestamp = $dateTime->getTimestamp();
        $todayStart = self::dateStartTime();

        return $timestamp >= ($todayStart - 86400) && $timestamp < $todayStart;
    }

    /**
     * 校验日期是否为明天的时间
     *
     * @param string|null $datetime
     * @param string $format        $date的格式
     * @return true
     * @example
     *  DateUtil::isTomorrow('2024-02-02 12:12:12');
     *  DateUtil::isTomorrow('2024-02-02', 'Y-m-d');
     */
    public static function isTomorrow(?string $datetime, string $format = 'Y-m-d H:i:s'): bool
    {
        $dateTime = self::getDateTime($datetime, $format);
        if (!$dateTime) {
            return false;
        }
        $timestamp = $dateTime->getTimestamp();
        $todayEnd = self::dateEndTime();

        return $timestamp > $todayEnd && $timestamp <= ($todayEnd + 86400);
    }

    /**
     * 日期比较
     *
     * @param string|null $date     日期或日期表达式
     * @param string $compare       用于日期比较的符号：'>', '>=', '<', '<=', '==', '!=', '<>', '<=>'
     * @param string|null $nextDate 日期或日期表达式
     * @param string $format        $date 和 $nextDate 的日期格式
     * @return bool|int 组合比较（<=>）时返回int，其他比较返回bool
     * @example
     *  DateUtil::compare('2030-01-01', '>', '2020-01-01', 'Y-m-d');
     *  DateUtil::compare('2020-01-02', '<=>', '2020-01-02', 'Y-m-d');
     */
    public static function compare(?string $date, string $compare, ?string $nextDate, string $format): bool|int
    {
        if (!in_array($compare, ['>', '>=', '<', '<=', '==', '!=', '<>', '<=>'], true)
            || !self::isDate($date, $format)
            || !self::isDate($nextDate, $format)
        ) {
            return false;
        }

        $compare = trim($compare);
        return eval("return \"strtotime($date)\" $compare \"strtotime($nextDate)\";");
    }

    /**
     * 格式为"Y-m-d"的日期比较
     *
     * @param string|null $date     第一个日期
     * @param string|null $nextDate 第二个日期
     * @param string $compare       用于日期比较的符号：'>', '>=', '<', '<=', '==', '!=', '<>', '<=>'
     * @return bool|int 组合比较（<=>）时返回int，其他比较返回bool
     * @example
     *  DateUtil::compareDate('2020-01-02', '2020-01-01');
     */
    public static function compareDate(?string $date, ?string $nextDate, string $compare = '>'): bool|int
    {
        return self::compare($date, $compare, $nextDate, 'Y-m-d');
    }

    /**
     * 格式为"Y-m-d H:i:s"的日期比较
     *
     * @param string|null $datetime     第一个日期
     * @param string|null $nextDatetime 第二个日期
     * @param string $compare           用于日期比较的符号：'>', '>=', '<', '<=', '==', '!=', '<>', '<=>'
     * @return bool|int 组合比较（<=>）时返回int，其他比较返回bool
     * @example
     *  DateUtil::compareDatetime('2020-01-01 12:12:12', '2020-01-01 11:11:11');
     */
    public static function compareDatetime(?string $datetime, ?string $nextDatetime, string $compare = '>'): bool|int
    {
        return self::compare($datetime, $compare, $nextDatetime, 'Y-m-d H:i:s');
    }

    /**
     * 获取两个日期的天数差值
     *
     * @param string|null $datetime     第一个日期
     * @param string|null $nextDatetime 第二个日期
     * @param string $format            $datetime 和 $nextDatetime 的日期格式
     * @return false|float
     * @example
     *  DateUtil::diffDays('2010-01-01', '2010-01-02', 'Y-m-d');
     */
    public static function diffDays(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d H:i:s'): false|float
    {
        $seconds = self::diffSeconds($datetime, $nextDatetime, $format);
        if ($seconds === false) {
            return false;
        }
        return $seconds / 86400;
    }

    /**
     * 获取两个日期的小时差值
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $format            $datetime 和 $nextDatetime 的日期格式
     * @return false|float
     * @example
     *  DateUtil::diffHours('2010-01-01', '2010-01-02', 'Y-m-d');
     */
    public static function diffHours(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d H:i:s'): false|float
    {
        $seconds = self::diffSeconds($datetime, $nextDatetime, $format);
        if ($seconds === false) {
            return false;
        }
        return $seconds / 3600;
    }

    /**
     * 获取两个日期的分钟差值
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $format            $datetime 和 $nextDatetime 的日期格式
     * @return false|float
     * @example
     *  DateUtil::diffMinutes('2010-01-01 12:12', '2010-01-01 13:12', 'Y-m-d H:i');
     */
    public static function diffMinutes(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d H:i:s'): false|float
    {
        $seconds = self::diffSeconds($datetime, $nextDatetime, $format);
        if ($seconds === false) {
            return false;
        }
        return $seconds / 60;
    }

    /**
     * 获取两个日期的秒差值
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $format            $datetime 和 $nextDatetime 的日期格式
     * @return false|int
     * @example
     *  DateUtil::diffSeconds('2010-01-01 13:12:00', '2010-01-01 13:12:10');
     */
    public static function diffSeconds(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d H:i:s'): false|int
    {
        $firstDatetime = self::getDateTime($datetime, $format);
        if (!$firstDatetime) {
            return false;
        }

        $secondDatetime = self::getDateTime($nextDatetime, $format);
        if (!$secondDatetime) {
            return false;
        }

        return $secondDatetime->getTimestamp() - $firstDatetime->getTimestamp();
    }

    /**
     * 格式化秒
     *
     * @param int|null $seconds
     * @param string $separator 时分秒的分隔符
     * @return string
     */
    public static function secondsToTime(?int $seconds, string $separator = ':'): string
    {
        if (is_null($seconds) || $seconds < 0) {
            $seconds = 0;
        }
        $format = '%02d' . $separator . '%02d' . $separator . '%02d';
        return sprintf($format, $seconds / 3600, ($seconds % 3600) / 60, $seconds % 60);
    }
}
