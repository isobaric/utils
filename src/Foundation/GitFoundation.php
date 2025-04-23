<?php

namespace Isobaric\Utils\Foundation;

class GitFoundation
{
    // true输出调试信息
    public bool $debug = false;

    // git命令的绝对路径
    public string $git = '/usr/bin/git';

    // 项目根目录(绝对路径)
    public string $applicationPath = '';

    // 远程仓库 TODO
    public string $repository = 'origin';

    // branch
    protected string $commendShowBranch = '[git] branch --show-current';

    // checkout
    protected string $commendCheckout = '[git] checkout [branch]';

    // pull
    protected string $commendPull = '[git] pull [repository] [branch]';

    // cherry -v
    protected string $commentCherry = '[git] cherry -v [compare] [flag]';

    // git show
    protected string $commendShowCommit = '[git] --no-pager show [commit] -s';

    // 文件最近一次修改日期
    protected string $commendFileLastModify = '[git] log -1 --format="%ad" -- [filename]';

    // 文件commit
    protected string $commendFileBlame = '[git] blame -L [line],[line] [filename]';

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
    protected function commitIdReplace(string $commend, string $commitId): string
    {
        return $this->replaceGitCommend($commend, ['[commit]'], [$commitId]);
    }

    /**
     * 分支名称替换
     * @param string $commend
     * @param string $compare
     * @param string $flag
     * @return string
     */
    protected function cherryReplace(string $commend, string $compare, string $flag): string
    {
        return $this->replaceGitCommend($commend, ['[compare]', '[flag]'], [$compare, $flag]);
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
     * @return string
     */
    protected function executeGitCommend(string $commend): string
    {
        $checkoutCommend = 'cd ' . $this->applicationPath . ' && ' . $commend;

        if ($this->debug) {
            $response = exec($checkoutCommend, $output);

            $this->outputEcho($output);
            unset($output);
        } else {
            $response = exec($checkoutCommend);
        }

        return $response;
    }

    /**
     * 内容输出
     * @param string|array|null $output
     * @param string            $filename
     * @return void
     */
    protected function outputEcho(null|string|array $output, string $filename = ''): void
    {
        if (!is_array($output)) {
            $output = [$output];
        }

        //$logPrefix = date('Y-m-d H:i:s') . ' ' . $this->application . ' | ';

        foreach ($output as $item) {

            // 如果是Null 则写入一个空行
            if (is_null($item)) {
                $content = PHP_EOL;
            } else {
                $content = $item . PHP_EOL;
            }

            if ($filename != '') {
                file_put_contents($filename, $content, FILE_APPEND);
            }

            echo $content;
        }
    }
}
