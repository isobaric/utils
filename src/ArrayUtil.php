<?php

namespace Isobaric\Utils;

class ArrayUtil
{
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
     * 获取数组唯一值 并重组下标
     * @param array $array
     * @return array
     */
    public static function uniqueValues(array $array): array
    {
        if (empty($array)) {
            return [];
        }
        return array_values(array_unique($array));
    }

    /**
     * 获取数组唯一值 并移除空值 且重组下标
     * @param array $array
     * @return array
     */
    public static function uniqueFilterValues(array $array): array
    {
        if (empty($array)) {
            return [];
        }
        return array_values(array_filter(array_unique($array)));
    }

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
     * 数组中的某个值是否为整数
     *
     * @param array $array
     * @param string|int $key
     * @return bool
     */
    public static function isIntValue(array $array, string|int $key): bool
    {
        if (!array_key_exists($key, $array)) {
            return false;
        }
        return NumberUtil::isInt($array[$key]);
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
     * 将数组的驼峰（大驼峰或小驼峰）格式下标转换为下划线格式下标
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

    /**
     * 将数组的下划线格式下标转为小驼峰格式下标
     *
     * @param array $array
     * @return array
     */
    public static function underlineIndexToCamel(array $array): array
    {
        foreach ($array as $index => $item) {
            unset($array[$index]);
            if (!is_int($index)) {
                $index = StringUtil::underlineToCamel($index);
            }
            $array[$index] = is_array($item) ? self::underlineIndexToCamel($item) : $item;
        }
        return $array;
    }

    /**
     * 反序列化数组的值 | 如果元素不是字符串则赋值false
     * @param array $array
     * @return array
     */
    public static function unserialize(array $array): array
    {
        return array_map(function ($item) {
            if (!is_string($item)) {
                return false;
            }
            return unserialize($item);
        }, $array);
    }

    /**
     * 将扁平化的数组转换为树形结构
     * @param array           $array       扁平化数组，其中每个元素需要有id和parent_id字段
     * @param int|string      $uniqueIndex 唯一值的下标（例：主键ID）
     * @param int|string      $parentIndex 父级接点的下标（例：parent_id）
     * @params int|string $childrenIndex 生成的子级节点下标名称（例：children）
     * @param int|string|null $parentId    父节点ID (默认为null，表示根节点)
     * @return array 树形结构数组
     */
    public static function tree(
        array           $array,
        int|string      $uniqueIndex,
        int|string      $parentIndex,
        int|string      $childrenIndex = 'children',
        int|string|null $parentId = null,
    ): array
    {
        $tree = [];
        // 过滤出父ID等于给定值的项
        foreach ($array as $item) {
            if ($item[$parentIndex] === $parentId) {
                // 递归调用自身，获取子节点
                $item[$childrenIndex] = self::tree(
                    $array, $uniqueIndex, $parentIndex, $childrenIndex, $item[$uniqueIndex]
                );
                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * 两个数组的交集
     * @param array $firstList
     * @param array $secondList
     * @return array
     */
    public static function intersect(array $firstList, array $secondList): array
    {
        if (count($firstList) > count($secondList)) {
            $tempList   = $secondList;
            $secondList = $firstList;
            $firstList  = $tempList;
        }

        return array_intersect($firstList, $secondList);
    }

    /**
     * 两个数组的差集
     * @param array $firstList
     * @param array $secondList
     * @return array
     */
    public static function diff(array $firstList, array $secondList): array
    {
        if (count($firstList) > count($secondList)) {
            $tempList   = $secondList;
            $secondList = $firstList;
            $firstList  = $tempList;
        }

        return array_diff($firstList, $secondList);
    }

    /**
     * 插入到数组指定位置
     * @param array $array  目标数组
     * @param int $position $array的位置
     * @param array $insert 向$array中插入的数组
     * @return array    返回插入后的数组
     */
    public static function arrayInsert(array $array, int $position, array $insert): array
    {
        return array_merge(array_splice($array, 0, $position), $insert, $array);
    }
}
