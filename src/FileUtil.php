<?php

namespace Isobaric\Utils;

class FileUtil
{
    /**
     * 获取扩展名称
     * @param string|null $filename 文件或url
     * @return string
     */
    public static function fileExtension(?string $filename): string
    {
        if (!str_contains($filename, '.')) {
            return '';
        }
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * 获取文件名称（不含扩展名称）
     * @param string|null $filename
     * @return string
     */
    public static function filename(?string $filename): string
    {
        if (empty($filename)) {
            return '';
        }
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * 获取包含扩展名称的文件名
     * @param string|null $filename
     * @return string
     */
    public static function basename(?string $filename): string
    {
        if (empty($filename)) {
            return '';
        }
        return pathinfo($filename, PATHINFO_BASENAME);
    }

    /**
     * 读取远程资源并下载到浏览器
     * @param string $url
     * @param string $filename
     * @return void
     */
    public static function remoteDownload(string $url, string $filename = ''): void
    {
        if (ob_get_length() > 0) {
            ob_end_clean();
            ob_start();
        }

        if ($filename == '') {
            $filename = basename($url);
        }
        $filename = rawurlencode($filename);

        // 允许任意来源跨域
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Expose-Headers: Content-Disposition");

        // 输出二进制流 | 强制下载
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        // 流式读取远程资源并输出到浏览器
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 128 * 1024);

        // 输出到浏览器
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * 文件下载
     * @param string $file
     * @return void
     */
    public static function fileDownload(string $file): void
    {
        if (ob_get_length() > 0) {
            ob_end_clean();
            ob_start();
        }

        // 文件大小
        $fileSize = filesize($file);

        // 以只读和二进制模式打开文件
        $handle = fopen($file, "rb");

        // 允许任意来源跨域
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Expose-Headers: Content-Disposition");

        // 这是一个文件流格式的文件
        Header("Content-type: application/octet-stream");

        // 请求范围的度量单位--字节
        Header("Accept-Ranges: bytes");

        // Content-Length是指定包含于请求或响应中数据的字节长度
        Header("Accept-Length: " . $fileSize);

        // 用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称
        Header("Content-Disposition: attachment; filename=" . self::basename($file));

        // 读取文件内容并直接输出到浏览器
        echo fread($handle, $fileSize);

        // 关闭句柄
        fclose($handle);
        @unlink($file);
        exit();
    }

    /**
     * 生成CSV文件
     *  注意：设置文件的MIME（“text/csv”）时，需要在使用输出函数（echo/print）之前，设置：header('Content-Type: text/csv');
     * @param string $file 含路径的文件名；例：/data/www/abc.csv
     * @param array $header 追加在$data之前的内容，一般是首行内容
     * @param array $data
     * @return bool
     */
    public static function createCsv(string $file, array $header, array $data): bool
    {
        if (empty($file) && empty($data)) {
            return false;
        }
        // 表头与数据合并
        array_unshift($data, $header);

        // 打开文件
        $handle = fopen($file, 'a');
        if (!$handle) {
            return false;
        }

        // 设置 BOM = UTF-8
        fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        //生成文件
        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }

            //写入失败
            if (!fputcsv($handle, $value)) {
                fclose($handle);
                unlink($file);
                return false;
            }
        }
        fclose($handle);
        return true;
    }
}