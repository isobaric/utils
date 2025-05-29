<?php

namespace Isobaric\Utils;

use Isobaric\Utils\Handler\ProcessHandler;
use Throwable;

class ProcessUtil extends ProcessHandler
{
    /**
     * 派发数据并等待执行结果
     *  参数参考：dispatch() 方法
     *  返回值参考：receive() 方法
     *
     * @param callable $call
     * @param mixed    $list
     * @param array    $other
     * @param int      $number
     * @param string   $model
     * @return array
     */
    public function dispatchWait(callable $call, mixed $list, array $other = [], int $number = 1, string $model = self::ELEMENT): array
    {
        $dispatch = $this->dispatch($call, $list, $other, $number, $model);

        return $this->receive($dispatch['unique_sign'], $dispatch['process_id']);
    }

    /**
     * 创建进程后派发数据并返回进程的的创建结果
     *
     * @param callable $call 处理$list的回调方法
     *  该方法应该至少接收一个参数
     *  $list或$list内的元素将传递给该方法的第一个参数
     *  $other的元素，将传递给改方案的其他参数
     *  参数使用call_user_func_array()方法传递，例：call_user_func_array($call, [$listItem, ...$other])
     *
     * @param mixed    $list 派发给进程的列表数据
     *  非数组格式的 $list 将创建一个子进程，并将 $list 和解包后的 $other 传递给 $call
     *  $call 的第一个参数格式应该与 $list 或 $list 内的元素格式一致
     *  传递多个元素时候，此时 $call 的第一个参数是数组格式的$list内的元素
     *
     * @param array    $other   派发给进程的其他参数值
     *  将以数组解包的形式传递给 $call 的第1个参数之后的参数
     *  例：call_user_func_array($call, [$listItem, ...$other])
     *
     * @param int      $number 数量
     *  当值等于0时，创建一个子进程，并将 $list 和解包后的 $other 传递给 $call
     *  当$model = ELEMENT 时，表示派发的 $list 元素数量
     *  当$model = PROCESS 时，表示创建的最大子进程数量
     *
     * @param string   $model  派发数据的模式
     *  仅当 $number 大于0时有效
     *  ELEMENT 派发 $number 个 $list 的元素到子进程中；生成 ceil(count($list) / $number) 个子进程
     *  PROCESS 最多生成 $number 个进程，并将 $list 的元素平均派发到子进程中
     *
     * @return array
     *  全部进程创建结束后，返回进程的的创建结果，返回值如下：
     *   unique_sign: 进程的唯一标识
     *   process_id: 子进程的ID列表；字段类型 lit数组
     *   create:     创建成功的进程数量；字段类型 int
     *   fail:       创建失败的进程数量；字段类型 int
     */
    public function dispatch(callable $call, mixed $list, array $other = [], int $number = 1, string $model = self::ELEMENT): array
    {
        // 当前进程的唯一标识
        $uniqueSign = md5(json_encode($call) . getmypid());

        // 创建临时存储目录 用于存储程序的执行结果 | 如果创建失败则 返回值data为空
        $filename = '';
        if (!is_dir($this->storagePath)) {
            if (@mkdir($this->storagePath, 0777, true)) {
                $filename = $uniqueSign . '.log';
            }
        }

        // 记录创建失败的进程数量
        $fail = 0;

        // 记录创建成功的进程ID
        $processId = [];

        // 非数组时 或 数量等于0时候 仅创建一个进程
        if (!is_array($list) || $number <= 0) {
            // 创建一个子进程，并将数据完整传递
            $this->forkCallback($filename, $fail, $processId, $call, $list, ...$other);
        } else {

            // 计算创建的子进程数量和子进程派发的元素数量
            $remainder = 0;
            if ($model == self::PROCESS) {
                $elementCount = count($list);

                // 需要的最小进程数
                $number = (int)floor($elementCount / $number);

                // 如果有余数，则将余数派发给进程（靠前的每个子进程分别接收一个余数元素）
                if ($elementCount > $number) {
                    $remainder = $elementCount % $number;
                }
            }

            // 派发数据
            while (count($list)) {

                // 如果有余数 需要将余数的数据派发进去
                if ($remainder > 0) {
                    $length = $number + 1;
                    $remainder--;
                } else {
                    $length = $number;
                }

                // 数据切割派发
                $listItem = array_splice($list, 0, $length);

                // 派发数据
                $this->forkCallback($filename, $fail, $processId, $call, $listItem, ...$other);
            }
        }

        // 进程结果存储 | 退出的进程数 应该等于创建的进程数
        return [
            'unique_sign' => $uniqueSign,
            'process_id' => $processId,
            'create' => count($processId),
            'fail' => $fail
        ];
    }

    /**
     * 获取进程的执行结果
     *
     * @param string $uniqueSign dispatch() 方法返回的 unique_sign 值
     * @param array  $processId  dispatch() 方法返回的 process_id 值
     * @return array 返回以下数据字段
     *  exit:  成功退出的进程数量；字段类型 int，等于 count($processId) 时表示全部成功
     *  error: 发生错误的进程数量；字段类型 int，等于0时 表示全部成功
     *  data:  $uniqueSign 对应的进程返回值不为Null时的结果列表；字段类型 list数组
     */
    public function receive(string $uniqueSign, array $processId): array
    {
        $processIds = $processId;

        // 成功退出的进程数量
        $exit = 0;

        // 发生错误的进程数量
        $error = 0;

        // 等待子进程全部退出
        while (true) {
            foreach ($processId as $key => $pid) {
                // 如果没有子进程退出立刻返回
                $wait = pcntl_waitpid($pid, $status, WNOHANG);

                switch ($wait) {
                    case -1:
                        // 发生错误
                        $error++;
                        unset($processId[$key]);
                        break;
                    case 0:
                        // 没有可用子进程 | 进程运行中
                        break;
                    default:
                        // 子进程退出
                        $exit++;
                        unset($processId[$key]);
                        break;
                }
            }

            // 进程全部退出 | 退出的进程数 应该等于创建的进程数
            if (empty($processId)) {
                break;
            }
            usleep(100000);
        }

        return [
            'exit' => $exit,
            'error' => $error,
            'data' => $this->getProcessResponse($uniqueSign . '.log', $processIds)
        ];
    }

    /**
     * 合并进程结果值
     * @param array $data receive方法返回值中的下标data的值
     * @param bool  $filter 是否过滤空值；
     * @param bool  $throw 返回值中有异常时，是否抛出异常；true 则抛出异常，false则过滤掉异常结果
     * @return array
     * @throws Throwable
     */
    public function combine(array $data, bool $filter = false, bool $throw = true): array
    {
        if (empty($data)) {
            return [];
        }

        $response = [];
        foreach ($data as $item) {
            // 处理进程的异常相应
            if ($item instanceof Throwable) {
                if ($throw) {
                    throw $item;
                }
                continue;
            }

            // 过滤空结果
            if ($filter && empty($item)) {
                continue;
            }

            array_push($response, ...$item);
        }

        return $response;
    }


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
}
