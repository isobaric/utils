<?php

namespace Isobaric\Utils;

use Isobaric\Utils\Foundation\GitFoundation;

class GitUtil extends GitFoundation
{
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
     * 文件最近修改时间
     * @param string      $filename
     * @param string|null $format   值为Null时，返回时间戳
     * @return string|int
     */
    public function fileLastModify(string $filename, null|string $format = 'Y-m-d H:i:s'): string|int
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
