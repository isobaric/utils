<?php

namespace Isobaric\Utils\Handler;

use Throwable;

abstract class ProcessHandler
{
    /**
     * 标识符
     * @var string
     */
    protected string $eol = "###PROCESS_UTIL###";

    /**
     * 临时存储目录 不存在则创建
     * @var string
     */
    protected string $storagePath = '/tmp/isobaric/';

    // 派发数据的模式
    const ELEMENT = 'element';

    const PROCESS = 'process';

    /**
     * 创建进程并派发任务
     * @param int      $fail
     * @param string   $filename    存储进程执行结果的临时文件名称（不含路径）为空则不存储
     * @param array    $processId
     * @param callable $call
     * @param mixed    ...$args
     * @return void
     */
    protected function forkCallback(string $filename, int &$fail, array &$processId, callable $call, mixed ...$args): void
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            // 进程创建失败
            $fail++;
        } else if ($pid) {
            // 进程
            $processId[] = $pid;
        } else {
            // 子进程
            try {
                $response = call_user_func_array($call, [...$args]);
            } catch (Throwable $throwable) {
                $response = $throwable;
            }

            $this->putProcessResponse(getmypid(), $filename, $response);
            exit(0);
        }
    }

    /**
     * 临时存储指定进程的内容到文件中
     * @param int|false $pid
     * @param string    $filename
     * @param mixed     $response
     * @return void
     */
    protected function putProcessResponse(int|bool $pid, string $filename, mixed $response): void
    {
        if ($pid !== false && $filename != '' && !is_null($response)) {
            file_put_contents($this->storagePath . $pid . $filename, serialize($response) . $this->eol, FILE_APPEND);
        }
    }

    /**
     * 解析进程返回结果
     *  如果存储文件不存在 或者 内容为空，则返回空数组
     *  如果存储文件存在 则将每一行的结果反序列化后返回
     * @param string $filename
     * @param array  $processIdList 进程ID
     * @return array
     */
    protected function getProcessResponse(string $filename, array $processIdList = []): array
    {
        if (empty($processIdList)) {
            $processIdList = [''];
        }

        $response = [];
        foreach ($processIdList as $processId) {

            $file = $this->storagePath . $processId . $filename;
            if (!is_file($file) || !is_readable($file)) {
                return [];
            }

            $storageContent = file_get_contents($file);
            @unlink($file);
            if ($storageContent === false) {
                continue;
            }

            $tempContent = explode($this->eol, $storageContent);
            array_pop($tempContent);
            $tempContent = array_map('unserialize', $tempContent);
            array_push($response, ...$tempContent);
            unset($tempContent);
        }

        return $response;
    }
}
