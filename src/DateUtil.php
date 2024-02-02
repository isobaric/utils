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
     * 获取DateTime对象
     *  $date格式必须与$format一致
     *
     * @param string|null $date
     * @param string|null $format
     * @return \DateTime|false
     */
    public static function getDateTime(?string $date, ?string $format = 'Y-m-d'): \DateTime|bool
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
     * 获取毫秒
     *
     * @return int
     */
    public static function millisecond(): int
    {
        return microtime(true) * 1000;
    }

    /**
     * 今天的开始时间
     *
     * @param string $format
     * @return string
     */
    public static function getTodayStart(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, self::getTodayStartTime());
    }

    /**
     * 今天的结束时间
     *
     * @param string $format
     * @return string
     */
    public static function getTodayEnd(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, self::getTodayEndTime());
    }

    /**
     * 周的开始时间
     *  返回$data所属周的开始时间，如果$data等于null则返当前周的开始时间
     *
     * @param string|null $date
     *  日期或日期表达式
     * @param string $format
     *  返回值的格式
     * @return string
     */
    public static function getWeekStart(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, self::getWeekStartTime($date));
    }

    /**
     * 周的结束时间
     *  返回$data所属周的结束时间，如果$data等于null则返当前周的结束时间
     *
     * @param string|null $date
     *  日期或日期表达式
     * @param string $format
     *  返回值的格式
     * @return string
     */
    public static function getWeekEnd(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, self::getWeekEndTime($date));
    }

    /**
     * 月的开始时间
     *  返回$data所属月的开始时间，如果$data等于null则返当前月的开始时间
     *
     * @param string|null $date
     *  日期或日期表达式
     * @param string $format
     *  返回值的格式
     * @return string
     */
    public static function getMonthStart(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, self::getMonthStartTime($date));
    }

    /**
     * 月的结束时间
     *  返回$data所属月的结束时间，如果$data等于null则返当前月的结束时间
     *
     * @param string|null $date
     *  日期或日期表达式
     * @param string $format
     *  返回值的格式
     * @return string
     */
    public static function getMonthEnd(?string $date = null, string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, self::getMonthEndTime($date));
    }

    /**
     * 今年的开始时间
     *
     * @param string $format
     * @return string
     */
    public static function getYearStart(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, self::getYearStartTime());
    }

    /**
     * 今年的结束时间
     *
     * @param string $format
     * @return string
     */
    public static function getYearEnd(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format, self::getYearEndTime());
    }

    /**
     * 今天开始的时间戳
     *
     * @return int
     */
    public static function getTodayStartTime(): int
    {
        return strtotime(date('Y-m-d') . ' 00:00:00');
    }

    /**
     * 今天结束的时间戳
     *
     * @return int
     */
    public static function getTodayEndTime(): int
    {
        return strtotime(date('Y-m-d') . ' 23:59:59');
    }

    /**
     * 获取格式化的日期 TODO
     *
     * @param string $format
     *  返回值的日期格式
     * @param string|null $date
     *  日期或日期表达式，如果是null则使用当前日期
     * @param string|null $dateFormat
     *  $date的日期格式，如果不是null则会比较$data是否与格式化之后的$data相等，如果不相等则返回空字符串
     * @return string
     */
    public static function formatDate(string $format, ?string $date = null, ?string $dateFormat = null): string
    {
        if ($format == '') {
            return '';
        }

        if (is_null($date)) {
            return date($format);
        }

        $dateTime = self::getDateTime($date, $dateFormat);
        if (!$dateTime) {
            return '';
        }
        return $dateTime->format($format);
    }

    /**
     * 周开始的时间戳
     *  返回$data所属周的开始时间戳，如果$data等于null则返当前周的开始时间戳
     *
     * @param string|null $date
     *  日期或日期表达式
     * @param string|null $format
     *   $date的日期格式；如果不是null则会比较$data是否与格式化之后的$data相等，如果不相等则返回false
     * @return false|int
     */
    public static function getWeekStartTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-d 00:00:00', $date, $format);
        if ($day == '') {
            return false;
        }

        $timestamp = strtotime($day);
        $week = intval(date('N', $timestamp));
        $revise = ($week - 1) * 86400;

        return $timestamp - $revise;
    }

    /**
     * 周结束的时间戳
     *  返回$data所属周的结束时间戳，如果$data等于null则返当前周的结束时间戳
     *
     * @param string|null $date
     *  日期或日期表达式
     * @param string|null $format
     *  $date的日期格式；如果不是null则会比较$data是否与格式化之后的$data相等，如果不相等则返回false
     * @return false|int
     */
    public static function getWeekEndTime(?string $date = null, ?string $format = null): false|int
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
     * 本月开始的时间戳
     *
     * @param string|null $date
     * @param string|null $format
     * @return false|int
     */
    public static function getMonthStartTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-01 00:00:00', $date, $format);
        if ($day == '') {
            return false;
        }

        return strtotime($day);
    }

    /**
     * 本月结束的时间戳 todo
     *
     * @param string|null $date
     * @param string|null $format
     * @return false|int
     */
    public static function getMonthEndTime(?string $date = null, ?string $format = null): false|int
    {
        $day = self::formatDate('Y-m-t 23:59:59', $date, $format);
        if ($day == '') {
            return false;
        }
        return strtotime($day);
    }

    /**
     * 本年开始的时间戳
     *
     * @return int
     */
    public static function getYearStartTime(): int
    {
        return strtotime(date('Y-01-01 00:00:00'));
    }

    /**
     * 本年结束的时间戳
     *
     * @return int
     */
    public static function getYearEndTime(): int
    {
        $lastMonthDay = date('t', strtotime('Y-12-01'));
        return strtotime(date('Y-12-' . $lastMonthDay . ' 23:59:59'));
    }
    
    /**
     * 日期格式转换
     *  $date格式必须与$format一致
     *  例：
     *  Y-m-d H:i:s 转 Y-m-d
     *  Y-m-d H:i:s 转 H:i:s
     *
     * @param string|null $date
     * @param string $format
     * @return string
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
     * 日期格式转换
     *  $datetime格式必须是Y-m-d H:i:s
     *  例：
     *  Y/m/d H:i:s 转 Y-m-d H:i:s
     *
     * @param string|null $datetime
     * @return string
     */
    public static function datetimeFormat(?string $datetime): string
    {
        return self::format($datetime, 'Y-m-d H:i:s');
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
        $todayStart = self::getTodayStartTime();

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
        $todayStart = self::getTodayStartTime();

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
        $todayEnd = self::getTodayEndTime();

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

    // 编写方法注释 TODO
}
