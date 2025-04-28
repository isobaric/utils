<?php

namespace Isobaric\Utils\Components;

trait ProcessComponent
{
    /**
     * 创建进程
     * @param callable                    $callback 在子进程内运行的回调方法
     * @param float|bool|int|string|array $data 派发给子进程的数据；如果不是数组，则仅创建一个进程，并派发数据
     * @param int                         $split 派发给单个进程的$data值的数量；仅$data为数组时有效
     *                                      用于将$data分割并传递给回调方法，将会创建 ceil(count($data) / $split) 个并行进程；
     *                                      如果$split为0，则不分割$data，既仅创建一个进程，并将$data派发到回调方法；
     * @return array    返回创建成功的子进程ID列表 和 创建失败的进程数量
     */
    private static function privateDispatch(callable $callback, float|bool|int|string|array $data, int $split = 1): array
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
