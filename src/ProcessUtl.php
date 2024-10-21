<?php

namespace Isobaric\Utils;

use Throwable;

class ProcessUtl
{
    /**
     * 调用 $callback 并重试 $limit 次；
     *  重试间隔：$limit * 0.5秒；例：第一次重试间隔为0.5秒，第二次则为1秒
     * @param callable $callback 回调方法
     * @param array    $args 回调方法的参数
     * @param int      $limit 重试次数
     * @return mixed    成功：返回回调结果，失败：如果有异常则抛出异常，没有异常则返回false
     */
    public static function retry(callable $callback, array $args = [], int $limit = 5): mixed
    {
        for ($counter = 1; $counter <= $limit; $counter++) {
            try {
                return call_user_func_array($callback, $args);
            } catch (Throwable $throwable) {
                if ($counter >= $limit) {
                    throw $throwable;
                } else {
                    usleep(500000 * $limit); // 延时 0.5 * $limit 秒
                }
            }
        }
        return false;
    }

    /**
     * 进程派发
     * @param callable $callback
     * @param array    $contentArr
     * @return array
     */
    public static function dispatch(callable $callback, array $contentArr = []): array
    {
        $fail = 0;
        $processIdList = [];
        foreach ($contentArr as $index => $content) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                // 进程创建失败
                $fail++;
            } else if ($pid) {
                // 父进程
                $processIdList[] = $pid;
            } else {
                // 子进程
                call_user_func_array($callback, [$index, $content]);
                exit(0);
            }
        }

        $processCount = count($processIdList);
        $success = 0;
        $error = 0;
        $miss = 0;
        while (true) {
            foreach ($processIdList as $processId) {
                $wait = pcntl_waitpid($processId, $status, WNOHANG);
                switch ($wait) {
                    case -1:
                        // 发生错误
                        $error++;
                        $processCount--;
                        break;
                    case 0:
                        // 没有可用子进程 | 进程运行中
                        $miss++;
                        break;
                    default:
                        // 子进程退出
                        $success++;
                        $processCount--;
                        break;
                }
            }

            // 进程全部退出
            if ($processCount <= 0) {
                break;
            }
            usleep(100000);
        }

        return [
            'complete' => $success + $error,
            'success' => $success,
            'error' => $error,
            'miss' => $miss,
            'fail' => $fail,
        ];
    }
}
