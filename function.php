<?php
/**
 * 助手函数
 * @author: 夜雨 yeyupl@qq.com
 */


/**
 * 调试输出
 * @param $val
 */
function dump($val) {
    if (is_array($val) || is_object($val)) {
        print_r($val);
    } elseif (is_bool($val)) {
        echo $val ? 'true' : 'false';
        echo PHP_EOL;
    } else {
        $val .= PHP_EOL;
        echo $val;
    }
}

function showLog($msg) {
    dump('[' . date('Y-m-d H:i:s') . ']' . $msg);
}
