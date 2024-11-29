<?php

namespace Isobaric\Utils\Components;

trait ProcessComponent
{
    /**
     * 创建进程
     * @param callable     $callback
     * @param string|array $data
     * @param int          $split
     * @return array    返回创建成功的子进程ID列表 和 创建失败的进程数量
     */
    private static function privateDispatch(callable $callback, string|array $data, int $split = 1): array
    {
        $fail = 0;
        $processIdList = [];

        if (is_string($data)) {
            $data = [$data];
        }

        // 开始派发数据
        while (count($data)) {
            // 数据切割派发
            if ($split <= 0) {
                $item = $data;
                $data = [];
            } else {
                $item = array_splice($data, 0, $split);
            }

            $pid = pcntl_fork();
            if ($pid == -1) {
                // 进程创建失败
                $fail++;
            } else if ($pid) {
                // 进程
                $processIdList[] = $pid;
            } else {
                // 子进程
                call_user_func_array($callback, [$item]);
                exit(0);
            }
        }

        return [$processIdList, $fail];
    }
}
