<?php
/**
 * zb自动交易
 * @author: 夜雨 yeyupl@qq.com
 */

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/function.php';

date_default_timezone_set('PRC');

$accessKey = '***';
$secretKey = '***';

$zbApi = new ZB\zbApi($accessKey, $secretKey);


$currency = 'bts_qc'; //交易对

$standardCurrency = 'qc'; //基准币
$targetCurrency = 'bts';  //目标币

$sleepTime = 10; //每次循环秒杀

$times = 0; //空操作次数
$maxTimes = 180;  //最大空操作次数
$cancelBuyTimes = 0;  //买单撤单次数
$cancelSellTimes = 0; //卖单撤单次数

$orderMaxAmount = 1000; //每次下单金额

$maxOrder = 2; //最大挂单数
$buyOrder = 0;  //委买单数
$sellOrder = 0; //委卖单数

$priceHistory = [];  //历史价格

while (true) {
    //可用数额
    $standardAmount = $zbApi->getAvailableAmount($standardCurrency);
    $targetAmount = $zbApi->getAvailableAmount($targetCurrency);

    //行情
    $ticker = $zbApi->ticker($currency);
    //现价
    $price = $ticker['ticker']['last'];

    $priceHistory[] = $price;

    //均价
    $avgPrice = array_sum($priceHistory) / count($priceHistory);

    $targetMaxAmount = round($orderMaxAmount / $price);

    if ($standardAmount < $orderMaxAmount && $targetAmount < $targetMaxAmount) {
        // 超过指定次数 撤消委单 重新挂
        if ($times >= $maxTimes) {
            //查询委托单
            $orders = $zbApi->getOrders($currency);
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
            $sellOrder = 0;
            $buyOrder = 0;
            $times = 0;
            sleep(3);
        } else {
            $times++;
            //showLog('无操作');
            sleep($sleepTime);
        }
        continue;
    }

    //市场深度
    $depth = $zbApi->depth($currency);

    if ($standardAmount > $orderMaxAmount && $buyOrder < $maxOrder) {
        // 如果还有qc 挂买单 现价折价1% 或者第5档加0.0001 取最大的

        //一直买不到 可能是单边行情 为防止一直不成交 挂单档次加上撤单次数因子
        if ($price >= $avgPrice) {
            //上涨行情 加速买入
            $bid = max(0, 3 - $cancelBuyTimes);
        } else {
            //下跌
            $bid = max(0, 4 - $cancelBuyTimes);
        }
        $buyPrice = max(round($price * 0.99, 3), $depth['bids'][$bid][0] + 0.0001);
        $buyAmount = floor($orderMaxAmount / $buyPrice);

        $result = $zbApi->order($currency, $buyPrice, $buyAmount, 1);
        if ($result['code'] == 1000) {
            $buyOrder++;
            $times = 0;
            $cancelBuyTimes = 0;
            showLog('委买：' . $buyPrice . '/' . $buyAmount);
        } else {
            dump('委买：' . $result['message'] . '(' . $buyPrice . '/' . $buyAmount . ')');
        }
    }
    if ($targetAmount > $targetMaxAmount && $sellOrder < $maxOrder) {
        // 如果还有bts 挂卖单 现价溢价1% 或者 第5档减少0.0001 取最小

        //一直卖不出 可能是单边行情 为防止一直不成交 挂单档次加上撤单次数因子
        if ($price >= $avgPrice) {
            $ask = max(0, 4 - $cancelSellTimes);
        } else {
            //下跌行情 加速卖出
            $ask = max(0, 3 - $cancelSellTimes);
        }

        $sellPrice = min(round($price * 1.01, 3), $depth['asks'][$ask][0] - 0.0001);
        $sellAmount = min($targetAmount, $targetMaxAmount);

        $result = $zbApi->order($currency, $sellPrice, $sellAmount, 0);
        if ($result['code'] == 1000) {
            $sellOrder++;
            $times = 0;
            showLog('委卖：' . $sellPrice . '/' . $sellAmount);
        } else {
            dump('委卖：' . $result['message'] . '(' . $sellPrice . '/' . $sellAmount . ')');
        }
    }
    sleep($sleepTime);
}
