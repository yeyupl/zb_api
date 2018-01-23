<?php
/**
 * zb自动交易
 * @author: 夜雨 yeyupl@qq.com
 */

date_default_timezone_set('PRC');
set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_WARNING);

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/function.php';

$accessKey = '***';
$secretKey = '***';

$zbApi = new ZB\ZBApi($accessKey, $secretKey);


$currency = 'bts_qc'; //交易对

$standardCurrency = 'qc'; //基准币
$targetCurrency = 'bts';  //目标币

$sleepTime = 10; //每次循环秒数

$times = 0; //空操作次数
$maxDummyTimes = 18;  //最大空操作次数

$hour = date('G');
if ($hour >= 0 && $hour < 9) {
    // 非成交活跃时间 加大撤单周期
    $maxDummyTimes = 30;
}

$cancelBuyTimes = 0;  //买单撤单次数
$cancelSellTimes = 0; //卖单撤单次数

$orderMinAmount = 100; //每次最小下单金额

$priceHistory = [];  //一个周期内的历史价格

while (true) {
    //可用余额
    $standardAmount = $zbApi->getAvailableAmount($standardCurrency);
    $targetAmount = $zbApi->getAvailableAmount($targetCurrency);

    //行情
    $ticker = $zbApi->ticker($currency);
    //现价
    $price = $ticker['ticker']['last'];

    $priceHistory[] = $price;

    //均价
    $avgPrice = array_sum($priceHistory) / count($priceHistory);

    $targetMinAmount = round($orderMinAmount / $price);

    //未完成的委单
    $orders = $zbApi->getUnfinishedOrdersIgnoreTradeType($currency);

    if ($standardAmount < $orderMinAmount && $targetAmount < $targetMinAmount) {
        // 超过指定次数 撤消委单 重新挂
        if ($times >= $maxDummyTimes) {
            if (!isset($orders['code'])) {
                $cancelOrder = 0;
                foreach ($orders as $order) {
                    //取消订单  0未成交 3部分成交
                    if (in_array($order['status'], [0, 3])) {
                        $zbApi->cancelOrder($currency, $order['id']);
                        $cancelOrder++;
                        if ($order['type'] == 1) {
                            $cancelBuyTimes++;
                        } else {
                            $cancelSellTimes++;
                        }
                    }
                }
                showLog('超时撤单：' . $cancelOrder);
            } else {
                showLog('没有委单');
            }
            $times = 0;
            sleep(3);
        } else {
            $times++;
            showLog('无操作');
            sleep($sleepTime);
        }
        continue;
    }

    //如果存在未完成委单 不继续挂单了
    if (isset($orders['code']) || !$orders) {

        //市场深度
        $depth = $zbApi->depth($currency);

        // 有足够qc 挂买单 现价折价1% 或者第5档加0.0001 取最大的
        if ($standardAmount > $orderMinAmount) {
            //一直买不到 可能是单边行情 为防止一直不成交 挂单档次加上撤单次数因子
            if ($price > $avgPrice) {
                //上涨行情 加速买入
                $bid = max(0, 4 - $cancelBuyTimes);
            } else {
                $bid = 4;
            }
            $buyPrice = max(round($price * 0.99, 3), $depth['bids'][$bid][0] + 0.0001);
            $buyAmount = floor($standardAmount / $buyPrice);

            $result = $zbApi->order($currency, $buyPrice, $buyAmount, 1);
            if ($result['code'] == 1000) {
                $times = 0;
                $cancelBuyTimes = 0;
                showLog('委买：' . $buyPrice . '/' . $buyAmount);
            } else {
                dump('委买：' . $result['message'] . '(' . $buyPrice . '/' . $buyAmount . ')');
            }
        } else if ($targetAmount > $targetMinAmount) {
            //有足够bts 挂卖单 现价溢价1% 或者 第5档减少0.0001 取最小
            //一直卖不出 可能是单边行情 为防止一直不成交 挂单档次加上撤单次数因子
            if ($price >= $avgPrice) {
                $ask = 0;
            } else {
                //下跌行情 加速卖出
                $ask = max(0, $cancelSellTimes);
            }

            $sellPrice = min(round($price * 1.01, 3), $depth['asks'][$ask][0] - 0.0001);
            $sellAmount = $targetAmount;

            $result = $zbApi->order($currency, $sellPrice, $sellAmount, 0);
            if ($result['code'] == 1000) {
                $times = 0;
                showLog('委卖：' . $sellPrice . '/' . $sellAmount);
            } else {
                dump('委卖：' . $result['message'] . '(' . $sellPrice . '/' . $sellAmount . ')');
            }
        }
    }
    sleep($sleepTime);
}
