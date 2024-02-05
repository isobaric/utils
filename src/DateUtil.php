<?php

namespace Isobaric\Utils;

class DateUtil
{
    /**
     * Y-m-d
     */
    const FORMAT_DATE = 'Y-m-d';

    /**
     * H:i:s
     */
    const FORMAT_TIME = 'H:i:s';

    /**
     * Y-m-d H:i:s
     */
    const FORMAT_DATETIME = 'Y-m-d H:i:s';

    /**
     * 获取$date的DateTime对象
     *
     * @param string|null $date
     *  日期或日期表达式；$date为空返回false
     *
     * @param string|null $format
     *  $date的日期格式；如果$format不是null则会对比$date是否与格式化之后的日期相等，如果不相等则返回false
     *
     * @return \DateTime|false
     *  成功：返回\DateTime对象
     *  失败：返回false
     *
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
     * @param string|null $date
     *  日期或日期表达式；$date为空返回空字符串
     *
     * @param string $format
     *  返回值格式
     *
     * @return string
     *  成功：返回$format格式的日期
     *  失败：返回空字符串
     *
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
     * @param string $format
     *  返回值的日期格式
     *
     * @param string|null $date
     *  日期或日期表达式
     *
     * @param string|null $dateFormat
     *  $date的日期格式
     *
     * @return string
     *  成功：返回格式化的日期字符串
     *      如果$date是null，$dateFormat不是null则返回空字符串；
     *      如果$date是null，$dateFormat也是null则返回format后的当前日期；
     *      如果$date不是null，$dateFormat是null则不验证$date的日期格式，返回format后的日期；
     *      如果$date不是null，$dateFormat不是null则会比较$date是否与格式化之后的日期相等，如果相等返回format后的日期；
     *  失败：返回空字符串
     *
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
     * @param string|null $date
     *  日期或日期表达式
     *
     * @return string
     *  成功：返回Y-m-d H:i:s格式的日期
     *  失败：返回空字符串
     *
     * @example
     *  DateUtil::formatDatetime('2023/12/12 12:12:12');
     *  DateUtil::formatDatetime('-1 day');
     */
    public static function formatDatetime(?string $date): string
    {
        return self::format($date, self::FORMAT_DATETIME);
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
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string|null $format
     *   $date的日期格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     *
     * @return false|int
     *  成功：返回日期的开始时间戳
     *  失败：返回false
     *
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
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string $format
     *  返回值的格式
     *
     * @return string
     *  成功：返回格式化后的日期开始时间
     *  失败：返回空字符串
     *
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
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string|null $format
     *   $date的日期格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     *
     * @return false|int
     *  成功：返回日期的结束时间戳
     *  失败：返回false
     *
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
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string $format
     *  返回值的格式
     * @return string
     *  成功：返回格式化后的日期的结束时间
     *  失败：返回空字符串
     *
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
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string|null $format
     *   $date的日期格式；如果$format不是null，则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     *
     * @return false|int
     *  成功：返回周的开始时间戳
     *  失败：返回false
     *
     * @example
     *  DateUtil::weekStartTime();
     *  DateUtil::weekStartTime('+3 day');
     *  DateUtil::weekStartTime('2030-05-10');
     *  DateUtil::weekStartTime('2030-05-10', 'Y-m-d');
     */
    public static function weekStartTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-d', $date, $format);
        if ($day == '') {
            return false;
        }

        $timestamp = strtotime($day);
        $week = intval(date('N', $timestamp));
        $revise = ($week - 1) * 86400;

        return $timestamp - $revise;
    }

    /**
     * 获取本周或指定日期所在周的开始时间
     *
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string $format
     *  返回值的格式
     *
     * @return string
     *  成功：返回格式化后周的开始时间
     *  失败：返回空字符串
     *
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
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string|null $format
     *  $date的日期格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     *
     * @return false|int
     *  成功：返回周的结束时间戳
     *  失败：返回false
     *
     * @example
     *  DateUtil::weekEndTime();
     *  DateUtil::weekEndTime('+3 day');
     *  DateUtil::weekEndTime('2030-05-10');
     *  DateUtil::weekEndTime('2030-05-10', 'Y-m-d');
     */
    public static function weekEndTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-d 23:59:59', $date, $format);
        if ($day == '') {
            return false;
        }

        $timestamp = strtotime($day);
        $week = intval(date('N', $timestamp));
        $revise = (7 - $week) * 86400;

        return $timestamp + $revise;
    }

    /**
     * 获取本周或指定日期所在周的结束时间
     *
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string $format
     *  返回值的格式
     *
     * @return string
     *  成功：返回格式化后周的结束时间
     *  失败：返回空字符串
     *
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
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string|null $format
     *  $date的日期格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     *
     * @return false|int
     *  成功：返回月的开始时间戳
     *  失败：返回false
     *
     * @example
     *  DateUtil::monthStartTime();
     *  DateUtil::monthStartTime('+3 day');
     *  DateUtil::monthStartTime('2030-05-10');
     *  DateUtil::monthStartTime('2030-05-10', 'Y-m-d');
     */
    public static function monthStartTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-01', $date, $format);
        if ($day == '') {
            return false;
        }

        return strtotime($day);
    }

    /**
     * 获取本月或指定日期所在月份的开始时间
     *
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string $format
     *  返回值的格式
     *
     * @return string
     *  成功：返回格式化后月分的开始时间
     *  失败：返回空字符串
     *
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
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string|null $format
     *  $date的日期格式；如果$format不是null则会比较$date是否与格式化之后的日期相等，如果不相等则返回false
     *
     * @return false|int
     *  成功：返回月的结束时间戳
     *  失败：返回false
     *
     * @example
     *  DateUtil::monthEndTime();
     *  DateUtil::monthEndTime('+3 day');
     *  DateUtil::monthEndTime('2030-05-10');
     *  DateUtil::monthEndTime('2030-05-10', 'Y-m-d');
     */
    public static function monthEndTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-t 23:59:59', $date, $format);
        if ($day == '') {
            return false;
        }
        return strtotime($day);
    }

    /**
     * 获取本月或指定日期所在月份的结束时间
     *
     * @param string|null $date
     *  日期或日期表达式，如果是null则默认日期为今天
     *
     * @param string $format
     *  返回值的格式
     *
     * @return string
     *  成功：返回格式化后月分的结束时间
     *  失败：返回空字符串
     *
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

    /** todo
     * 年开始的时间戳
     *
     * @param int|null $year
     *  表示年的数字；如果等于null则返回当前年的开始时间戳，如果小于1970则返回false
     * @return false|int
     */
    public static function yearStartTime(?int $year = null): false|int
    {
        if (is_null($year) ) {
            return strtotime(date('Y-01-01'));
        }

        if ($year < 1970) {
            return false;
        }
        return strtotime(date($year .'-01-01'));
    }

    /**
     * 今年的开始时间
     *
     * @param int|null $year
     *  表示年的数字；如果等于null则返回当前年的开始时间，如果小于1970则返回空字符串
     * @param string $format
     *  返回值的格式
     * @return string
     *  成功：返回格式化后的日期；
     *  失败：返回空字符串
     */
    public static function getYearStart(?int $year = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::yearStartTime($year);
        return $time ? date($format, $time) : '';
    }

    /**
     * 年结束的时间戳
     *
     * @param int|null $year
     *  表示年的数字；如果等于null则返回当前年的开始时间戳，如果小于1970则返回false
     * @return false|int
     */
    public static function getYearEndTime(?int $year = null): false|int
    {
        if (is_null($year) ) {
            return strtotime(date('Y-12-31 23:59:59'));
        }

        if ($year < 1970) {
            return false;
        }
        return strtotime(date($year .'-12-31 23:59:59'));
    }

    /**
     * 年的结束时间
     *
     * @param int|null $year
     *  表示年的数字；如果等于null则返回当前年的结束时间，如果小于1970则返回空字符串
     * @param string $format
     *  返回值的格式
     * @return string
     *  成功：返回格式化后的日期；
     *  失败：返回空字符串
     */
    public static function getYearEnd(?int $year = null, string $format = 'Y-m-d H:i:s'): string
    {
        $time = self::getYearEndTime($year);
        return $time ? date($format, $time) : '';
    }

    /**
     * 日期格式校验
     *  $date格式必须与$format一致
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
        $result = self::format($date, $format);
        return $result && $result == $date;
    }

    /**
     * 日期格式校验
     *  $datetime格式必须是Y-m-d H:i:s
     *
     * @param string|null $datetime
     *
     * @return bool
     */
    public static function isDatetime(?string $datetime): bool
    {
        return self::isDate($datetime, 'Y-m-d H:i:s');
    }

    /**
     * 是否为时间段
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $format
     * @return bool
     */
    public static function isInterval(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d'): bool
    {
        $datetime = self::getDateTime($datetime, $format);
        if (!$datetime) {
            return false;
        }
        $nextDatetime = self::getDateTime($nextDatetime, $format);
        if (!$nextDatetime) {
            return false;
        }

        return ($nextDatetime->getTimestamp() - $datetime->getTimestamp()) >= 0;
    }

    /**
     * 是否为时间段
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @return bool
     */
    public static function isIntervalDatetime(?string $datetime, ?string $nextDatetime): bool
    {
        return self::isInterval($datetime, $nextDatetime, 'Y-m-d H:i:s');
    }

    /**
     * 是否是未来日期
     *  $date格式必须与$format一致
     *
     * @param string|null $date
     * @param string $format
     * @return bool
     */
    public static function isFuture(?string $date, string $format = 'Y-m-d'): bool
    {
        $dateTime = self::getDateTime($date, $format);
        if (!$dateTime) {
            return false;
        }

        return $dateTime->getTimestamp() < time();
    }

    /**
     * 是否是未来日期
     *  $datetime格式必须是Y-m-d H:i:s
     *
     * @param string|null $datetime
     *
     * @return bool
     */
    public static function isFutureDatetime(?string $datetime): bool
    {
        return self::isFuture($datetime, 'Y-m-d H:i:s');
    }

    /**
     * 日期是否为今天
     *
     * @param string|null $datetime
     * @param string $format
     * @return true
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
     * 日期是否为今天
     *
     * @param string $datetime
     * @param string $format
     * @return true
     */
    public static function isYesterday(string $datetime, string $format = 'Y-m-d H:i:s'): bool
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
     * 日期是否为今天
     *
     * @param string $datetime
     * @param string $format
     * @return true
     */
    public static function isTomorrow(string $datetime, string $format = 'Y-m-d H:i:s'): bool
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
     * 日期大小比较
     *  $datetime/$nextDatetime格式必须与$format一致
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $compare
     *  >, >=, <, <=, ==, <>
     * @param string $format
     *
     * @return bool
     */
    public static function compare(?string $datetime, ?string $nextDatetime, string $compare = '>', string $format = 'Y-m-d'): bool
    {
        if (!self::isDate($datetime, $format) || !self::isDate($nextDatetime, $format)) {
            return false;
        }

        $compare = trim($compare);
        return eval("return \"strtotime($datetime)\" $compare \"strtotime($nextDatetime)\";");
    }

    /**
     * 日期大小比较
     *  $datetime/$nextDatetime格式必须是Y-m-d H:i:s
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $compare
     * @return bool
     */
    public static function compareDatetime(?string $datetime, ?string $nextDatetime, string $compare = '>'): bool
    {
        return self::compare($datetime, $nextDatetime, $compare, 'Y-m-d H:i:s');
    }

    /**
     * 计算两个日期的年度差值
     *  格式必须与$format一致
     *
     * @param string|null $date
     * @param string|null $nextDate
     * @param string $format
     * @return bool|int
     */
    public static function diffYears(?string $date, ?string $nextDate, string $format = 'Y-m-d H:i:s'): bool|int
    {
        $firstDatetime = self::getDateTime($date, $format);
        if (!$firstDatetime) {
            return false;
        }

        $secondDatetime = self::getDateTime($nextDate, $format);
        if (!$secondDatetime) {
            return false;
        }

        return $secondDatetime->format('Y') - $firstDatetime->format('Y');
    }

    /**
     * 计算两个日期的累计天数差值
     *  日期格式必须与$format一致
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $format
     * @return bool|int
     */
    public static function diffDays(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d H:i:s'): bool|int
    {
        $seconds = self::diffSeconds($datetime, $nextDatetime, $format);
        if ($seconds === false) {
            return false;
        }

        if ($seconds == 0) {
            return 0;
        }

        return floor($seconds / 86400);
    }

    /**
     * 计算两个日期的小时差值
     *  $datetime/$nextDatetime格式必须与$format一致
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $format
     * @return bool|int
     */
    public static function diffHours(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d H:i:s'): bool|int
    {
        $seconds = self::diffSeconds($datetime, $nextDatetime, $format);
        if ($seconds === false) {
            return false;
        }

        if ($seconds == 0) {
            return 0;
        }

        return floor($seconds / 3600);
    }

    /**
     * 计算两个日期的分钟差值
     *  $datetime/$nextDatetime格式必须与$format一致
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $format
     * @return bool|int
     */
    public static function diffMinutes(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d H:i:s'): bool|int
    {
        $seconds = self::diffSeconds($datetime, $nextDatetime, $format);
        if ($seconds === false) {
            return false;
        }

        if ($seconds == 0) {
            return 0;
        }

        return floor($seconds / 60);
    }

    /**
     * 计算两个日期的秒差值
     *  $datetime/$nextDatetime格式必须与$format一致
     *
     * @param string|null $datetime
     * @param string|null $nextDatetime
     * @param string $format
     * @return bool|int
     */
    public static function diffSeconds(?string $datetime, ?string $nextDatetime, string $format = 'Y-m-d H:i:s'): bool|int
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
}
