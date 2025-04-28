<?php

namespace Isobaric\Utils;

use Throwable;

class ProcessUtil
{
    /**
     * 标识符
     * @var string
     */
    private static string $processEol = "###PROCESS_UTIL###";

    /**
     * 临时存储目录
     * @var string
     */
    private static string $storage = '/tmp/';

    /**
     * 进程结果值
     * @var array
     */
    private static array $dispatchResponse = [];

    /**
     * 调用 $callback 并重试 $limit 次；
     *  重试间隔：$limit * 0.5秒；例：第一次重试间隔为0.5秒，第二次则为1秒
     * @param callable $callback 回调方法
     * @param array    $args 回调方法的参数
     * @param int      $limit 重试次数
     * @return mixed    成功：返回回调结果；失败：超过重试次数后，如果有异常则抛出异常，没有异常则返回false
     */
    public static function retry(callable $callback, array $args = [], int $limit = 3): mixed
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
     * 进程派发，并等待进程执行结果
     *  注意：1 当子进程创建完成后 当前方法会进入等待状态 直至全部子进程执行结束
     *
     * @param callable $callback 接收$data的回调方法
     * @param mixed    $data 派发给回调方法的数据
     * @param int|null $child 创建的最大子进程数量
     * @param int|null $split 派发给单个子进程的$data元素数量
     * @return array
     *
     * 参数说明：
     * @see self::dispatchAsync()
     * 返回值说明：
     * @see self::dispatchWait()
     */
    public static function dispatch(callable $callback, mixed $data, ?int $child = null, ?int $split = null): array
    {
        self::dispatchAsync($callback, $data, $child, $split);

        return self::dispatchWait($callback);
    }

    /**
     * 创建进程，数据派发结束后返回，不等待进程执行结果
     *  注意：0 仅用于在命令行模式下调用
     *       1 应该在调用当前方法之后且脚本结束之前，调用当前类中的 dispatchWait() 方法，以确保不会产生僵尸进程
     *       2 如果多个子进程使用了主进程的连接资源或句柄，将会产生异常，使用当前方法之前，需主动断开资源链接或句柄。
     *       3 仅当$child为null时，$split生效
     *       4 $child不为null时， 值为1、$split的最小值
     * @param callable $callback 在子进程内运行的回调方法；该方法固定接收一个参数，类型与$data一致
     * @param mixed    $data 派发给子进程的数据；如果不是数组，则仅创建一个进程，并派发数据
     * @param int|null $child 创建的最大子进程数量
     * @param int|null $split 派发给单个子进程的$data元素数量；$data为数组或$child为null时有效
     *                     用于分割$data内的元素并传递给回调方法；将会创建 ceil(count($data) / $split) 个并行进程；
     *                     如果$split为0，则不分割$data，既仅创建一个进程，并将$data派发到回调方法；
     * @return void
     */
    public static function dispatchAsync(callable $callback, mixed $data, ?int $child = null, ?int $split = null): void
    {
        // 如果临时目录存在 且可写，则将内容存储到临时文件中 并返回
        $callbackUniqueSign = self::dispatchUniqueString($callback);
        $storageFile = self::dispatchStorageFile($callbackUniqueSign);

        $fail = 0;
        $processIdList = [];

        // 仅派发一个进程
        if (!is_array($data) || $split == 0) {
            self::dispatchForkCallback($fail, $storageFile, $processIdList, $callback, $data);
            goto DISPATCH_FORK_END;
        }

        if (is_null($child)) {
            $split = max(1, $split);
        } else {
            $child = max(1, $child);
            $split = ceil(count($data) / $child);
        }

        // 数组时 开始派发数据
        while (count($data)) {
            // 数据切割派发
            if ($split <= 0) {
                $item = $data;
                $data = [];
            } else {
                $item = array_splice($data, 0, $split);
            }
            // 派发数据
            self::dispatchForkCallback($fail, $storageFile, $processIdList, $callback, $item);
        }

        DISPATCH_FORK_END:
        // 进程结果存储 | 退出的进程数 应该等于创建的进程数
        self::$dispatchResponse[$callbackUniqueSign] = [
            'process_id' => $processIdList,
            'create' => count($processIdList),
            'fail' => $fail
        ];
    }

    /**
     * 等待进程退出并返回结果集
     *  注意：0 仅用于在命令行模式下调用
     *       1 仅当临时目录存在且具有可写权限，且进程返回值不是null时，返回值中的data有值
     *       2 多次使用 dispatchAsync() 方法执行相同回调，则当前方法返回值中的data是多次调用的完整结果集；其他返回值则是最后一次的进程派发结果
     * @param callable $callback    进程派发的回调函数
     * @return array        全部子进程退出后，返回进程的的执行结果，返回值如下：
     *                          process_id: 子进程的ID，字段类型 lit数组
     *                          create:     创建成功的进程数量，字段类型 int
     *                          fail:       创建失败的进程数量，字段类型 int
     *                          exit:       成功退出的进程数量，字段类型 int
     *                          error:      发生错误的进程数量，字段类型 int
     *                          data:       进程返回值不为Null时的结果列表，字段类型 list数组
     */
    public static function dispatchWait(callable $callback): array
    {
        // 从内存中获取回调的进程派发数据
        $callbackUniqueSign = self::dispatchUniqueString($callback);
        $dispatchResult = self::$dispatchResponse[$callbackUniqueSign] ?? [];
        if (empty($dispatchResult)) {
            return [];
        }
        // 移除已使用的结果 释放内存
        unset(self::$dispatchResponse[$callbackUniqueSign]);
        $processIdList = $dispatchResult['process_id'];

        // 等待子进程全部退出
        $exit = 0;
        $error = 0;
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

            // 进程全部退出 | 退出的进程数 应该等于创建的进程数
            if (empty($processIdList)) {
                break;
            }
            usleep(100000);
        }

        $dispatchResult['exit'] = $exit;
        $dispatchResult['error'] = $error;
        $dispatchResult['data'] = self::dispatchResponseDecode($callbackUniqueSign);

        return $dispatchResult;
    }

    /**
     * 创建进程并派发任务
     * @param int      $fail
     * @param string   $file
     * @param array    $processIdList
     * @param callable $callback
     * @param mixed    ...$args
     * @return void
     */
    private static function dispatchForkCallback(
        int &$fail,
        string $file,
        array &$processIdList,
        callable $callback,
        mixed ...$args
    ): void
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            // 进程创建失败
            $fail++;
        } else if ($pid) {
            // 进程
            $processIdList[] = $pid;
        } else {
            // 子进程
            try {
                $response = call_user_func_array($callback, [...$args]);
            } catch (Throwable $throwable) {
                $response = $throwable;
            }

            if ($file != '' && !is_null($response)) {
                file_put_contents($file, serialize($response) . self::$processEol, FILE_APPEND);
            }
            exit(0);
        }
    }

    /**
     * 用于临时存储子进程结果的文件
     * @param string $filename
     * @return string
     */
    private static function dispatchStorageFile(string $filename): string
    {
        // 如果临时目录存在 且可写，则将内容存储到临时文件中 并返回 file_exists
        $cachePath = self::$storage . '/isobaric/';
        if (!file_exists($cachePath)) {
            @mkdir($cachePath);
        }

        if (!is_writable($cachePath)) {
            return '';
        }

        return $cachePath . $filename . '.log';
    }

    /**
     * 生成唯一值
     * @param string|array|callable $data |array $string $string
     * @return string
     */
    private static function dispatchUniqueString(string|array|callable $data): string
    {
        return md5(json_encode($data) . getmypid());
    }

    /**
     * 解析进程返回结果
     *  如果存储文件不存在 或者 内容为空，则返回空数组
     *  如果存储文件存在 则将每一行的结果反序列化后返回
     * @param string $filename
     * @return array
     */
    private static function dispatchResponseDecode(string $filename): array
    {
        $cacheFile = self::dispatchStorageFile($filename);
        if ($cacheFile == '' || !is_file($cacheFile) || !is_readable($cacheFile)) {
            return [];
        }
        $storage = file_get_contents($cacheFile);
        @unlink($cacheFile);
        if ($storage === false) {
            return [];
        }
        $storageList = explode(self::$processEol, $storage);
        array_pop($storageList);

        return array_map('unserialize', $storageList);
    }

    /**
     * 合并进程结果值
     * @param array $data dispatch方法返回值中的下标data的值
     * @param bool  $isFilter 是否过滤空值；
     * @param bool  $isThrowable 返回值中有异常时，是否抛出异常；true 则抛出异常，false则过滤掉异常
     * @return array
     * @throws Throwable
     */
    public static function dispatchResponseMerge(array $data, bool $isFilter = false, bool $isThrowable = true): array
    {
        if (empty($data)) {
            return [];
        }

        $response = [];
        foreach ($data as $item) {
            // 处理进程的异常相应
            if ($item instanceof Throwable) {
                if ($isThrowable) {
                    throw $item;
                }
                continue;
            }

            // 过滤空结果
            if ($isFilter && empty($item)) {
                continue;
            }

            array_push($response, ...$item);
        }

        return $response;
    }

    /**
     * 获取PHP的可执行文件路径
     * @return string
     */
    public static function getBinPath(): string
    {
        $phpBinFile = [
            '/usr/local/php-8.1.12/bin/php',
            '/opt/bitnami/php/bin/php',
            '/usr/bin/php81',
            '/usr/bin/php',
        ];
        $php = '';
        foreach ($phpBinFile as $binFile) {
            if (file_exists($binFile) && is_executable($binFile)) {
                $php = $binFile;
                break;
            }
        }
        return $php;
    }
}
