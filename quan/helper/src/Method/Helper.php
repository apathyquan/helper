<?php
/*
 * @Descripttion: 助手函数
 * @version: 
 * @Author: Quan
 * @Date: 2021-01-09 13:51:27
 * @LastEditors: Quan
 * @LastEditTime: 2021-01-18 14:14:24
 */

declare(strict_types=1);


namespace Helper\Method;

use Quan\Helper\Method\TraitBlock\UploadTrait;
use Throwable;

class Helper
{
    use UploadTrait;


    /**
     * @description: 树形表结构更新层级关系字段
     * @Author: Quan
     * @param 对应模型 $model
     * @param 旧层级关系数组 $oldArr
     * @param 新层级关系数组 $newArr
     * @param 主键值 $primary_val
     * @param 层级关系字段 $parent_arr_column
     * @param 主键字段 $primary_column
     * @param 父级字段 $parent_id_column
     * @return {*}
     */
    static public  function childParentIdArrUpdate(object $model, array $oldArr, array $newArr, string $primary_val, string $parent_arr_column = 'parent_id_arr', string $primary_column = 'id', string $parent_id_column = 'parent_id'): void
    {
        if (json_encode($oldArr) == json_encode($newArr)) {
            return;
        }
        $childParentIdArr = $model->where($parent_id_column, '=', $primary_val)->value($parent_arr_column);
        $str = implode(',', json_decode($childParentIdArr, true));
        $child = $model->where($parent_arr_column, 'like', '[' . $str . ',' . '%')->orWhere($parent_arr_column, 'like', $childParentIdArr)->get([$primary_column, $parent_arr_column]);
        $childArr = $child->toArray();
        $groupArr = [];
        foreach ($childArr as $v) {
            $v[$parent_arr_column] = json_decode($v[$parent_arr_column], true);
            $index = array_search(end($oldArr), $v[$parent_arr_column]);
            array_splice($v[$parent_arr_column], 0, $index + 1);
            array_unshift($v[$parent_arr_column], ...$newArr);
            $v[$parent_arr_column] = json_encode($v[$parent_arr_column]);
            $groupArr[$v[$parent_arr_column]][$parent_arr_column] = $v[$parent_arr_column];
            $groupArr[$v[$parent_arr_column]][$primary_column][] = $v[$primary_column];
        }
        foreach ($groupArr as $v) {
            $model->whereIn($primary_column, $v[$primary_column])->update([$parent_arr_column => $v[$parent_arr_column]]);
        }
    }
    /**
     * treeData 生成树状数据
     * @author Quan
     * @param  array $items  原数据
     * @param  string $son 存放孩子节点字段名
     * @param  string $id 排序显示的键，一般是主键 
     * @param  array  $pid  父id
     * @return array  树状数据
     */
    public function treeData(array $items = [], string $pid = 'parent_id', string $id = 'id', string $son = 'child'): array
    {
        $tree = [];
        $tmpData = []; //临时数据
        foreach ($items as $item) {
            $item[$son] = [];
            $tmpData[$item[$id]] = $item;
        }
        foreach ($items as $item) {
            if (isset($tmpData[$item[$pid]])) {
                $tmpData[$item[$pid]][$son][] = &$tmpData[$item[$id]];
            } else {
                $tree[] = &$tmpData[$item[$id]];
            }
        }
        unset($tmpData);
        return $tree;
    }
    /**
     * @description: 号码打码
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    public function mobileMosaicStr(string $mobile): string
    {
        return  substr($mobile, 0, 3) . '****' . substr($mobile, 7);
    }
    /**
     * @description:数据分组 
     * @param {dataArr:需要分组的数据；keyStr:分组依据} 
     * @author Quan
     * @return: 
     */
    static protected  function dataGroup(array $dataArr, string $keyStr): array
    {
        $newArr = [];
        foreach ($dataArr as $k => $val) {    //数据根据日期分组
            $newArr[$val[$keyStr]][] = $val;
        }
        return $newArr;
    }
    /**
     * 时间戳人性化转化
     * @param $time
     * @return string
     */
    static protected  function timeTran(int $time): string
    {
        $t = time() - $time;
        $f = [
            '31536000' => '年',
            '2592000' => '个月',
            '604800' => '星期',
            '86400' => '天',
            '3600' => '小时',
            '60' => '分钟',
            '1' => '秒'
        ];
        foreach ($f as $k => $v) {
            if (0 != $c = floor($t / (int)$k)) {
                return $c . $v . '前';
            }
        }
    }

    /**
     * @description: xml 转换数组
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    public function xmlToArray(string $xml): array
    {

        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $arr;
    }




    /**
     * @description: 根据经纬度计算距离，单位km
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    public function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6367; //地区半径6367km
        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;
        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;
        $calcLongitude      = $lng2 - $lng1;
        $calcLatitude       = $lat2 - $lat1;
        $stepOne            = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo            = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance, 1);
    }
    /**
     * @description:根据某字段进行排序，正序
     * @param {type} 
     * @return: 
     */
    protected function sortArr(array $pointArr, string $str): array
    {
        $len = count($pointArr);
        for ($i = 1; $i < $len; $i++) {
            //该层循环用来控制每轮 冒出一个数 需要比较的次数
            for ($k = 0; $k < $len - $i; $k++) {
                if (isset($pointArr[$k + 1][$str]) && isset($pointArr[$k][$str])) {
                    if ($pointArr[$k][$str] > $pointArr[$k + 1][$str]) {
                        $tmp = $pointArr[$k + 1]; // 声明一个临时变量
                        $pointArr[$k + 1] = $pointArr[$k];
                        $pointArr[$k] = $tmp;
                    }
                }
            }
        }
        return $pointArr;
    }
    /**
     * @description:过滤数组为null和''的字段,array_filter也能过滤,但其默认会把0、false这样具体的值过滤掉
     * @Author: Quan
     * @param  array  $arr
     * @return array
     */
    static protected function filterArray(array $arr): array
    {
        foreach ($arr as $k => $v) {
            if ($v === '' || $v === null) {
                unset($arr[$k]);
            }
        }
        return $arr;
    }
    /**
     * @description:文件下载，下载完自动删除，配合前端文件流下载 
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    static protected function fileDownload(string $filePath): void
    {
        $fp = fopen($filePath, "r");
        $file_size = filesize($filePath);
        $buffer = 1024;  //设置一次读取的字节数，每读取一次，就输出数据（即返回给浏览器）
        $file_count = 0; //读取的总字节数
        //向浏览器返回数据 
        while (!feof($fp) && $file_count < $file_size) {
            $file_con = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_con;
        }
        fclose($fp);
        //下载完成后删除文件
        if ($file_count >= $file_size) {
            unlink($filePath);
        }
    }

    /**
     * @description:文件更新，新文件内容更新到原文件中，并删除新文件 
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    protected function fileUpdate(string $newPath, string $oldPath): void
    {
        $path = $newPath;
        $oldPath = $oldPath;
        if (file_exists($path) && $path !== $oldPath && $newPath) {
            $content = file_get_contents($path);
            file_put_contents($oldPath, $content);
            unlink($path);
        }
    }
    /**
     * @description:批量删除文件 
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    protected function deleteBatchFile(array  $paths): void
    {
        array_map(function ($path) {
            if (!file_exists($path)) {
                $path = $path;
            }
            file_exists($path) && is_file($path) && unlink($path);
        }, $paths);
    }
    /**
     * @description:删除单个文件 
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    protected function deleteFile(string   $path): void
    {

        if (!file_exists($path)) {
            $path = $path;
        }
        file_exists($path) && is_file($path) && unlink($path);
    }
    /**
     * @description: 10进制转36进制
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    protected function createCode(string $number): string
    {
        static $sourceString = [
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
            'a', 'b', 'c', 'd', 'e', 'f',
            'g', 'h', 'i', 'j', 'k', 'l',
            'm', 'n', 'o', 'p', 'q', 'r',
            's', 't', 'u', 'v', 'w', 'x',
            'y', 'z'
        ];

        $num = $number;
        $code = '';
        while ($num) {
            $mod = bcmod($num, '36');
            $num = bcdiv($num, '36');
            $code = "{$sourceString[$mod]}{$code}"; //邀请码拼接
        }
        //判断code的长度
        if (empty($code[4]))
            $code = str_pad($code, 5, '0', STR_PAD_LEFT); //长度不够拼接'0'

        return $code;
    }
    /**
     * @description: 删除html和空格
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    public function htmlTagFilter(?string $str): string
    {
        $resStr = '';
        if (!empty($str)) {
            $tmpStr = strip_tags($str);
            $resStr = str_replace(array("&nbsp;", "&ensp;", "&emsp;", "&thinsp;", "&zwnj;", "&zwj;", "&ldquo;", "&rdquo;"), "", $tmpStr);
        }
        return $resStr;
    }
    /**
     * @description: 字符串截取
     * @Author: Quan
     * @param {type} 
     * @return: 
     */
    public function strCut(?string $str, int $startIndex, int $length, string $prefix = '...'): string
    {
        $resStr = '';
        if (!empty($str)) {
            $resStr = mb_strlen($str) > $length ? mb_substr($str, $startIndex, $length) . $prefix : $str;
        }
        return $resStr;
    }
    /**
     * @description: 驼峰命名转下划线命名
     * @param {type} 
     * @return {type} 
     */
    public  function uncamelize(string $camelCaps, string $separator = '_'): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }
    /**
     * 下划线转驼峰
     * 思路:
     * step1.原字符串转小写,原字符串中的分隔符用空格替换,在字符串开头加上分隔符
     * step2.将字符串中每个单词的首字母转换为大写,再去空格,去字符串首部附加的分隔符.
     * @param $uncamelized_words
     * @param string $separator
     * @return string
     */
    protected function camelize(string $uncamelized_words, string $separator = '_'): string
    {
        $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));
        return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
    }
}
