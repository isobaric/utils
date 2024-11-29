<?php

namespace Isobaric\Utils;

use Isobaric\Utils\Components\ProcessComponent;
use Throwable;

class ProcessUtl
{
    use ProcessComponent;

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
     *  注意：1 当子进程创建完成后 当前方法会进入等待状态 直至全部子进程执行结束
     *       2 当子进程全部结束后 当前方法返回进程的运行状态结果
     * @param callable $callback    接收$data的回调方法
     * @param array    $list        派发给回调方法的数据；数据下标不变；
     * @param int      $split       派发给单个进程的$data值的数量；
     *                                  用于将$data分割并传递给回调方法，将会创建 ceil(count($list) / $split) 个并行进程；
     *                                  如果$split为0，则不分割$data，既仅创建一个进程，并将$data派发到回调方法；
     * @return array    全部子进程退出后，返回进程的的执行结果
     */
    public function dispatchSync(callable $callback, array $list = [], int $split = 1): array
    {
        $dispatch = self::privateDispatch($callback, $list, $split);

        return self::dispatchWait(...$dispatch);
    }

    /**
     * 进程派发
     *  注意：1 当进程创建完成后，立即返回创建成功的子进程ID列表 和 创建失败的进程数量
     *       2 应该在调用当前方法之后 脚本结束之前，调用当前类中的 dispatchWait() 方法，以确保不会产生僵尸进程
     * @param callable $callback    接收$data的回调方法，
     * @param array    $data        派发给回调方法的数据；数据下标不变；
     * @param int      $split       派发给单个进程的$data值的数量；
     *                                  用于将$data分割并传递给回调方法，将会创建 ceil(count($data) / $split) 个并行进程；
     *                                  如果$split为0，则不分割$data，既仅创建一个进程，并将$data派发到回调方法；
     * @return array    返回创建成功的子进程ID列表 和 创建失败的进程数量
     */
    public static function dispatchAsync(callable $callback, array $data = [], int $split = 0): array
    {
        return self::privateDispatch($callback, $data, $split);
    }

    /**
     * 等待进程退出
     * @param array $processIdList  子进程ID
     * @param int   $fail   创建失败的进程数
     * @return array        全部子进程退出后，返回进程的的执行结果
     */
    public static function dispatchWait(array $processIdList, int $fail = 0): array
    {
        $exit = 0;
        $error = 0;
        $processCount = count($processIdList);

        // 等待子进程全部退出
        while (true) {
            foreach ($processIdList as $key => $processId) {
                // 如果没有子进程退出立刻返回
                $wait = pcntl_waitpid($processId, $status, WNOHANG);

                switch ($wait) {
                    case -1:
                        // 发生错误
                        $error++;
                        unset($processIdList[$key]);
                        break;
                    case 0:
                        // 没有可用子进程 | 进程运行中
                        break;
                    default:
                        // 子进程退出
                        $exit++;
                        unset($processIdList[$key]);
                        break;
                }
            }

            // 进程全部退出
            if (empty($processIdList)) {
                break;
            }
            usleep(100000);
        }

        // 退出的进程数 应该等于创建的进程数
        return [
            // 创建成功的进程数
            'create' => $processCount,

            // 退出的进程数
            'exit' => $exit,

            // 执行失败的进程数
            'error' => $error,

            // 创建失败的进程数
            'fail' => $fail,
        ];
    }

    /**
     * 执行sleep并输出内容
     * @param string $echo
     * @param int    $sleepTime sleep的时间 单位：秒
     * @param int    $interval  输出内容的时间间隔 单位：秒
     * @return void
     */
    public static function consoleEcho(string $echo = '', int $sleepTime = 58, int $interval = 30): void
    {
        if ($interval == 0 || $sleepTime == 0) {
            return;
        }

        $ceil = ceil($sleepTime / $interval);

        while ($ceil--) {
            sleep($interval);
            echo date('Y-m-d H:i:s') . $echo . PHP_EOL;
        }
    }
}
