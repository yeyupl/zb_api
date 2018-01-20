<?php
/**
 * zb自动交易
 * @author: 夜雨 yeyupl@qq.com
 */

require_once __DIR__ . '/vendor/autoload.php';

/**
 * 是否运行在CLI模式下
 * @return bool
 */
function isCli() {
    return php_sapi_name() == 'cli';
}

/**
 * 调试输出
 * @param $val
 */
function dump($val) {
    if (is_array($val) || is_object($val)) {
        if (!isCli()) {
            echo '<pre>';
        }
        print_r($val);
        if (!isCli()) {
            echo '</pre>';
        }
    } elseif (is_bool($val)) {
        echo $val ? 'true' : 'false';
        echo PHP_EOL;
    } else {
        if ($val) {
            $val .= PHP_EOL;
            echo isCli() ? $val : nl2br($val);
        }
    }
}

$zbApi = new ZB\zbApi();

dump($zbApi->markets());
