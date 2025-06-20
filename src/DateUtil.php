<?php

namespace Isobaric\Utils;

class DateUtil
{
    /**
     * 获取13位长度的毫妙时间戳
     * @return int
     */
    public static function millisecond(): int
    {
        return microtime(true) * 1000;
    }

    /**
     * 获取第一个时间和第二个时间的差值
     *
     * @param string|null $firstData
     * @param string|null $secondDate
     *
     * @return int
     *  $firstData 或 $secondDate 为空或解析为时间戳失败时 返回0
     *  成功：返回 $firstData - $secondDate 的秒的差值
     */
    public static function dateSubtractionSecond(?string $firstData, ?string $secondDate): int
    {
        if (empty($firstData) || empty($secondDate)) {
            return 0;
        }

        $firstSecond = strtotime($firstData);
        $secondSecond = strtotime($secondDate);
        if (!$firstSecond || !$secondSecond) {
            return 0;
        }

        return $firstSecond - $secondSecond;
    }

    /**
     * 将时间戳(秒)输出为时分秒
     *
     * @param int|float|null $second 时间戳（秒）
     *  如果传参是int 则返回时分秒
     *  如果传参是float 则返回时分秒毫秒
     *  如果传参是null 则默认为取前时间戳，返回时分秒
     *
     * @param string $separator 时分秒的分隔符
     * @return string 返回格式化的时分秒；例：12:30:20、120:59:59
     */
    public static function secondToTime(null|int|float $second = null, string $separator = ':'): string
    {
        if (is_null($second)) {
            $second = time();
        }

        $hours = floor($second / 3600);
        $minutes = floor(($second % 3600) / 60);
        $seconds = $second % 60;

        $format = '%02d' . $separator . '%02d' . $separator . '%02d';

        if (is_float($second)) {
            $millisecond = substr(number_format(fmod($second, 1), 3), 1);
            $format .= $millisecond;
        }

        return sprintf($format, $hours, $minutes, $seconds);
    }

    /**
     * 将时间戳等分为指定个数
     * @param int $second   时间戳（秒）
     * @param int $xPointNumber  X轴展示的点的数量
     * @return array 返回等分后的指定个数的格式化秒；例：[00:00:10, 00:00:30, 00:00:60]
     */
    public static function secondTimeAxis(int $second, int $xPointNumber): array
    {
        $division = $xPointNumber - 1;
        $average = $second / $division;
        $startTime = 0;

        $axis = [];
        for ($i = $division; $i >= 0; $i--) {
            $nextTime = $i * $average;
            // 时间点 00:00:10
            $pointTime = self::secondToTime($second - $nextTime);

            // 时间分割 2025-01-01 12:12:12
            $nextSlot = ($division - $i) * $average;
            $slotTime = $startTime + $nextSlot;
            // 构建返回值的外层数组
            $axis[$pointTime] = $slotTime;
        }
        return $axis;
    }

    /**
     * 将日期时差等分为指定个数
     * @param string|null $startDate 开始日期 例：2020-12:12 00:00:00
     * @param string|null $endDate  结束日期 例：2020-12:13 00:00:00
     * @param int $pointNumber 返回的时间点数量
     * @return array
     *  成功：返回等分后的指定个数的日期；例：[2020-12:12 00:00:10, 2020-12:12 00:00:30, 2020-12:12 00:00:60]
     *  失败：返回空数组
     */
    public static function timeAxisSubtraction(?string $startDate, ?string $endDate, int $pointNumber): array
    {
        // 直播时长：单位秒
        $diffSecond = self::dateSubtractionSecond($endDate, $startDate);
        $startTime = strtotime($startDate);
        $division = $pointNumber - 1;
        $average = $diffSecond / $division;

        $axis = [];
        for ($i = $division; $i >= 0; $i--) {
            $nextTime = $i * $average;
            // 时间点 00:00:10
            $pointTime = self::secondToTime($diffSecond - $nextTime);

            // 时间分割 2025-01-01 12:12:12
            $nextSlot = ($division - $i) * $average;
            $slotTime = date('Y-m-d H:i:s', $startTime + $nextSlot);
            // 构建返回值的外层数组
            $axis[$pointTime] = $slotTime;
        }
        return $axis;
    }
}
