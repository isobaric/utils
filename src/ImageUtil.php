<?php

namespace Isobaric\Utils;

class ImageUtil
{
    /**
     * 图片添加水印
     * @param string $originalImg  原始图片
     * @param string $watermarkImg 水印图片
     * @param string $outputImg    添加水印后的图片
     * @param int    $pct          水印透明度，取值0-100，值越小透明度越高
     * @return void
     */
    public static function watermark(string $originalImg, string $watermarkImg, string $outputImg, int $pct = 10): void
    {
        // 加载原始图片
        $originalImage = imagecreatefrompng($originalImg);
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);

        // 加载水印图片
        $watermarkImage = imagecreatefrompng($watermarkImg);
        $watermarkWidth = imagesx($watermarkImage);
        $watermarkHeight = imagesy($watermarkImage);

        // 计算水印的重复次数
        $repeatX = ceil($originalWidth / $watermarkWidth);
        $repeatY = ceil($originalHeight / $watermarkHeight);

        // 遍历并放置水印
        for ($x = 0; $x < $repeatX; $x++) {
            for ($y = 0; $y < $repeatY; $y++) {
                // 计算水印放置的位置
                $destX = $x * $watermarkWidth;
                $destY = $y * $watermarkHeight;

                // 将水印以一定的透明度放置到图片上
                imagecopymerge($originalImage, $watermarkImage, $destX, $destY, 0, 0, $watermarkWidth, $watermarkHeight, $pct);
            }
        }
        // 保存带有水印的新图片
        imagejpeg($originalImage, $outputImg);

        // 清理内存
        imagedestroy($originalImage);
        imagedestroy($watermarkImage);
    }
}
