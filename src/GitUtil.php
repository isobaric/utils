<?php

namespace Isobaric\Utils;

use Isobaric\Utils\Handler\GitHandler;

class GitUtil extends GitHandler
{
    /**
     * @param string $applicationRoot
     * @param bool   $debug
     */
    public function __construct(string $applicationRoot = '', bool $debug = false)
    {
        if ($applicationRoot != '') {
            $this->setApplicationRoot($applicationRoot);
        }
        $this->debug = $debug;
    }

    /**
     * 分支切换
     * @param string $branch
     * @return bool
     */
    public function checkout(string $branch = 'master'): bool
    {
        // 查看当前分支名称
        $currentBranch = $this->executeGitCommend($this->binReplace($this->commendShowBranch));
        // 分支名称不相同 切换分支
        if ($branch != $currentBranch) {
            $this->executeGitCommend($this->branchReplace($this->commendCheckout, $branch));
        }
        return true;
    }

    /**
     * 拉取远程分支
     * @param string $branch
     * @param bool   $output
     * @return string|array     $output=true时，返回数组；$output=false时，返回字符串；
     */
    public function pull(string $branch = 'master', bool $output = false): string|array
    {
        return $this->executeGitCommend($this->pullReplace($this->commendPull, $branch), $output);
    }

    /**
     * 分支比对
     * @param string $upstream 比较的分支
     * @param string $head    被比较的分支
     * @param bool   $output
     * @return string|array $output=true时，返回数组；$output=false时，返回字符串；
     */
    public function cherry(string $upstream, string $head, bool $output = false): string|array
    {
        return $this->executeGitCommend($this->cherryReplace($this->commentCherry, $upstream, $head), $output);
    }

    /**
     * 查看commitID的提交信息
     * @param string $commitId
     * @param bool   $output
     * @return string|array     $output=true时，返回数组；$output=false时，返回字符串；
     */
    public function showCommit(string $commitId, bool $output = false): string|array
    {
        return $this->executeGitCommend($this->commitReplace($this->commendShowCommit, $commitId), $output);
    }

    /**
     * 文件最近修改时间
     * @param string      $filename
     * @param string|null $format   值为Null时，返回时间戳；例：$format = 'Y-m-d H:i:s'
     * @return string|int
     */
    public function fileLastModify(string $filename, null|string $format = null): string|int
    {
        $modifyDate = $this->executeGitCommend($this->filenameReplace($this->commendFileLastModify, $filename));
        if (empty($modifyDate)) {
            return '';
        }

        if (is_null($format)) {
            return strtotime($modifyDate);
        }

        return date($format, strtotime($modifyDate));
    }

    /**
     * 文件指定行的commit信息
     * @param string $filename
     * @param int    $line
     * @return string
     */
    public function lineBlame(string $filename, int $line): string
    {
        return $this->executeGitCommend($this->blameReplace($this->commendLineBlame, $line, $filename));
    }

    /**
     * 文件的blame
     * @param string $filename
     * @return array
     */
    public function fileBlame(string $filename): array
    {
        return $this->executeGitCommend($this->filenameReplace($this->commendFileBlame, $filename), true);
    }
}
