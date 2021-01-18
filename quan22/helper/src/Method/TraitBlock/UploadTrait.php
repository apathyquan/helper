<?php
/*
 * @Descripttion: 
 * @version: 
 * @Author: Quan
 * @Date: 2021-01-09 14:24:17
 * @LastEditors: Quan
 * @LastEditTime: 2021-01-09 15:43:18
 */

declare(strict_types=1);

namespace App\Common\Method\TraitBlock;

use Exception;

trait UploadTrait
{
    /**
     * @description: 图片上传
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    public function uploadImg(string $fileString, string  $appoint_path = '', string  $prefix = '', string $maxWidth = ''): string
    {

        $file = request()->file($fileString);
        extract($this->fileImgStoreBlock($file, $appoint_path, $prefix,  $maxWidth));
        return  $url;
    }

    /**
     * @description:图片文件存储逻辑 
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    private function fileImgStoreBlock(object $file, string  $appoint_path = '', string  $prefix = '', string $maxWidth = ''): array
    {

        //判断文件是否上传成功
        if (!$file->isValid()) {
            throw new Exception('文件上传失败');
        }
        $ext = $file->getExtension();  // 扩展名
        if (!in_array($ext, ['jpg', 'PNG', 'JPG', 'JPEG', 'png', 'jpeg', 'webp'])) {
            throw new Exception('仅支持jpg/jpeg,png,web');
        }
        $base_path = '/public/uploads/';
        $final_path = $base_path . date("Ymd", time());
        if ($appoint_path != '') {
            $final_path = $base_path . $appoint_path . '/' . date("Ymd", time());
        } 

        // 源文件最终文件名
        $file_name = $this->getFileName($ext, $prefix);
        //TODO:压缩待完成
        $save_path = BASE_PATH . $final_path;
        //检查文件夾是否存在-不存在以配置文件中的目录创建文件夹
        if (!$this->folderExists($save_path))  $this->mkdir($save_path);
        $file->moveTo($save_path . '/' . $file_name);
        $url =  $final_path . '/' . $file_name; // 源图片路径
        // $thumb_url = '';

        return compact('url');
    }
    //创建文件夹
    private function mkdir($folder)
    {
        mkdir($folder, 0777, true);
    }

    //检查文件夾是否存在
    private function folderExists($folder)
    {
        return is_dir($folder) ? true : false;
    }
    /**
     * @description: 获取文件名
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    public function getFileName(string $ext = 'png', string  $prefix = ''): string
    {
        return  date('Ymd') . '_' . $this->randName(20) . '_' . $prefix  . '.' . $ext;
    }

    private static function randName(int $len = 30): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234565789'), 0, $len);
    }
}
