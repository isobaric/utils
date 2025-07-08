<?php

namespace Isobaric\Utils\Handler;

use RuntimeException;

class GitHandler
{
    // 当前一次执行的命令
    public string $execCommend = '';

    // true输出调试信息
    public bool $debug;

    // git命令的绝对路径
    public string $git = '/usr/bin/git';

    // 项目根目录(绝对路径)
    public string $applicationRoot = '';

    // 远程仓库
    public string $repository = 'origin';

    // branch
    protected string $commendShowBranch = '[git] branch --show-current';

    // checkout
    protected string $commendCheckout = '[git] checkout [branch]';

    // pull
    protected string $commendPull = '[git] pull [repository] [branch]';

    // cherry -v
    protected string $commentCherry = '[git] cherry -v [upstream] [head]';

    // git show
    protected string $commendShowCommit = '[git] --no-pager show [commit] -s';

    // 文件最近一次修改日期
    protected string $commendFileLastModify = '[git] log -1 --format="%ad" -- [filename]';

    // 文件指定行的commit
    protected string $commendLineBlame = '[git] blame -L [line],[line] [filename]';

    // 文件的commit
    protected string $commendFileBlame = '[git] blame [filename]';

    /**
     * 设置工作目录
     * @param string $applicationRoot
     * @return void
     */
    public function setApplicationRoot(string $applicationRoot): void
    {
        $this->applicationRoot = $applicationRoot;
        $this->checkApplicationRoot();
    }

    /**
     * @return void
     */
    protected function checkApplicationRoot(): void
    {
        if (!is_dir($this->applicationRoot)) {
            throw new RuntimeException('无效的路径：' . $this->applicationRoot);
        }
    }

    /**
     * @param bool $debug
     * @return void
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @param string $repository
     * @return void
     */
    public function setRepository(string $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * @param string $git
     * @return void
     */
    public function setGit(string $git): void
    {
        $this->git = $git;
    }

    /**
     * 获取当前一次执行的命令
     * @return string
     */
    public function getExecCommend(): string
    {
        return $this->execCommend;
    }

    /**
     * @param string $commend
     * @return array|string|string[]
     */
    protected function binReplace(string $commend): array|string
    {
        return $this->replaceGitCommend($commend);
    }

    /**
     * 分支名称替换
     * @param string $commend
     * @param string $branchName
     * @return string
     */
    protected function branchReplace(string $commend, string $branchName): string
    {
        return $this->replaceGitCommend($commend, ['[branch]'], [$branchName]);
    }

    /**
     * pull命令替换
     * @param string $commend
     * @param string $branchName
     * @return string
     */
    protected function pullReplace(string $commend, string $branchName): string
    {
        return $this->replaceGitCommend($commend, ['[repository]', '[branch]'], [$this->repository, $branchName]);
    }

    /**
     * 文件名称替换
     * @param string $commend
     * @param string $filename
     * @return string
     */
    protected function filenameReplace(string $commend, string $filename): string
    {
        return $this->replaceGitCommend($commend, ['[filename]'], [$filename]);
    }

    /**
     * blame替换
     * @param string $commend
     * @param int    $line
     * @param string $filename
     * @return string
     */
    protected function blameReplace(string $commend, int $line, string $filename): string
    {
        return $this->replaceGitCommend($commend, ['[line]', '[line]', '[filename]'], [$line, $line, $filename]);
    }

    /**
     * commitId替换
     * @param string $commend
     * @param string $commitId
     * @return string
     */
    protected function commitReplace(string $commend, string $commitId): string
    {
        return $this->replaceGitCommend($commend, ['[commit]'], [$commitId]);
    }

    /**
     * 分支名称替换
     * @param string $commend
     * @param string $upstream
     * @param string $head
     * @return string
     */
    protected function cherryReplace(string $commend, string $upstream, string $head): string
    {
        return $this->replaceGitCommend($commend, ['[upstream]', '[head]'], [$upstream, $head]);
    }

    /**
     * git命名替换
     * @param string $commend
     * @param array  $search
     * @param array  $replace
     * @return string
     */
    protected function replaceGitCommend(string $commend, array $search = [], array $replace = []): string
    {
        array_unshift($search, '[git]');
        array_unshift($replace, $this->git);
        return str_replace($search, $replace, $commend);
    }

    /**
     * 执行git命令
     * @param string $commend
     * @param bool   $getOutput
     * @return string|array
     */
    protected function executeGitCommend(string $commend, bool $getOutput = false): string|array
    {
        // 检查工作目录
        $this->checkApplicationRoot();
        $this->execCommend = 'cd ' . $this->applicationRoot . ' && ' . $commend;

        if ($this->debug) {
            $this->outPrint($this->execCommend);
        }

        if ($getOutput) {
            exec($this->execCommend, $response);
        } else {
            $response = exec($this->execCommend);
            if ($this->debug) {
                $this->outPrint($response);
            }
        }

        return $response;
    }

    /**
     * 内容输出
     * @param string|array|null $output
     * @param string            $filename 输入内容到文件
     * @param string            $prefix   输出的内容前缀
     * @return void
     */
    public function outPrint(null|string|array $output, string $filename = '', string $prefix = ''): void
    {
        if (!is_array($output)) {
            $output = [$output];
        }

        foreach ($output as $item) {

            // 如果是Null 则写入一个空行
            if (is_null($item)) {
                $content = PHP_EOL;
            } else {
                if ($prefix == '') {
                    $content = date('Y-m-d H:i:s') . ' ' . $item . PHP_EOL;
                } else {
                    $content = $prefix . ' ' . date('Y-m-d H:i:s') . ' ' . $item . PHP_EOL;
                }
            }

            if ($filename != '') {
                file_put_contents($filename, $content, FILE_APPEND);
            }

            echo $content;
        }
    }
}
