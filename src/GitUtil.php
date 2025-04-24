<?php

namespace Isobaric\Utils;

use Isobaric\Utils\Foundation\GitFoundation;

class GitUtil extends GitFoundation
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
     * @return string
     */
    public function pull(string $branch = 'master'): string
    {
        return $this->executeGitCommend($this->pullReplace($this->commendPull, $branch));
    }

    /**
     * 分支比对
     * @param string $upstream 比较的分支
     * @param string $head    被比较的分支
     * @return string 返回$head中存在但$upstream中缺少的提交
     */
    public function cherry(string $upstream, string $head): string
    {
        return $this->executeGitCommend($this->cherryReplace($this->commentCherry, $upstream, $head));
    }

    /**
     * 分支比对
     * @param string $upstream 比较的分支
     * @param string $head    被比较的分支
     * @return array 返回$head中存在但$upstream中缺少的提交
     */
    public function cherryOutput(string $upstream, string $head): array
    {
        return $this->executeGitCommend($this->cherryReplace($this->commentCherry, $upstream, $head), true);
    }

    /**
     * 查看commitID的提交信息
     * @param string $commitId
     * @return string
     */
    public function showCommit(string $commitId): string
    {
        return $this->executeGitCommend($this->commitReplace($this->commendShowCommit, $commitId));
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
    public function fileBlame(string $filename, int $line): string
    {
        return $this->executeGitCommend($this->blameReplace($this->commendFileBlame, $line, $filename));
    }
}
