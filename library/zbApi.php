<?php
/**
 * zb.com API接口封装
 * @author: 夜雨 yeyupl@qq.com
 */

namespace ZB;

class zbApi {

    private $accessKey = '***';
    private $secretKey = '***';
    private $hqUrl = 'http://api.zb.com/data/v1/';
    private $tradeUrl = 'https://trade.zb.com/api/';

    private $http;

    public function __construct() {
        $this->http = new http();
    }

    /** 参数签名
     * @param array $params
     * @return string
     */
    public function createSign($params = []) {
        $preSign = http_build_query($params, '', '&');
        $sign = hash_hmac('md5', $preSign, sha1($this->secretKey));
        $params['sign'] = $sign;
        $params['reqTime'] = time() * 1000;
        return http_build_query($params, '', '&');
    }


    /**
     * 发起行情请求
     * @param $method
     * @param array $params
     * @return array|mixed|stdClass|string
     */
    public function hqRequest($method, $params = []) {
        return $this->http->get($this->hqUrl . $method, $params, true);
    }

    /**
     * 发起交易请求
     * @param $method
     * @param array $params
     * @return array|mixed|stdClass|string
     */
    public function tradeRequest($method, $params = []) {
        $params['accesskey'] = $this->accessKey;
        $params['method'] = $method;
        $params = $this->createSign($params);
        return $this->http->get($this->tradeUrl . $method, $params, true);
    }

    /**
     * 市场配置
     * @return array|mixed|stdClass|string
     */
    public function markets() {
        return $this->hqRequest('markets', []);
    }


    /**
     * 行情
     * @param string $market
     * @return array|mixed|stdClass|string
     */
    public function ticker($market = 'btc_usdt') {
        return $this->hqRequest('ticker', ['market' => $market]);
    }


    /**
     * 市场深度
     * @param string $market
     * @param int $size
     * @return array|mixed|stdClass|string
     */
    public function depth($market = 'btc_usdt', $size = 3) {
        return $this->hqRequest('depth', ['market' => $market, 'size' => $size]);
    }


    /**
     * 历史成交
     * @param string $market
     * @return array|mixed|stdClass|string
     */
    public function trades($market = 'btc_usdt') {
        return $this->hqRequest('trades', ['market' => $market]);
    }


    /**
     * K线
     * @param string $market
     * @return array|mixed|stdClass|string
     */
    public function kline($market = 'btc_usdt') {
        return $this->hqRequest('kline', ['market' => $market]);
    }


    /**
     * 委托下单
     * @param $currency
     * @param $price
     * @param $amount
     * @param int $tradeType
     * @return array|mixed|stdClass|string
     */
    public function order($currency, $price, $amount, $tradeType = 1) {
        return $this->tradeRequest('order', ['currency' => $currency, 'price' => $price, 'amount' => $amount, 'tradeType' => $tradeType]);
    }


    /**
     * 取消委托
     * @param $currency
     * @param $id
     * @return array|mixed|stdClass|string
     */
    public function cancelOrder($currency, $id) {
        return $this->tradeRequest('cancelOrder', ['currency' => $currency, 'id' => $id]);
    }


    /**
     * 获取委托单
     * @param $currency
     * @param $id
     * @return mixed
     */
    public function getOrder($currency, $id) {
        return $this->tradeRequest('getOrder', ['currency' => $currency, 'id' => $id]);
    }

    /**
     * 获取多个委托买单或卖单，每次请求返回10条记录
     * @param $currency
     * @param int $pageIndex
     * @param int $tradeType
     * @return array|mixed|stdClass|string
     */
    public function getOrders($currency, $pageIndex = 1, $tradeType = 1) {
        return $this->tradeRequest('getOrders', ['currency' => $currency, 'pageIndex' => $pageIndex, 'tradeType' => $tradeType]);
    }

    /**
     * 获取多个委托买单或卖单，每次请求返回pageSize<100条记录
     * @param $currency
     * @param int $pageIndex
     * @param int $pageSize
     * @param int $tradeType
     * @return array|mixed|stdClass|string
     */
    public function getOrdersNew($currency, $pageIndex = 1, $pageSize = 1, $tradeType = 1) {
        return $this->tradeRequest('getOrdersNew', ['currency' => $currency, 'pageIndex' => $pageIndex, 'pageSize' => $pageSize, 'tradeType' => $tradeType]);
    }


    /**
     * 获取未成交或部份成交的买单和卖单，每次请求返回pageSize<=10条记录
     * @param $currency
     * @param int $pageIndex
     * @param int $pageSize
     * @return array|mixed|stdClass|string
     */
    public function getUnfinishedOrdersIgnoreTradeType($currency, $pageIndex = 1, $pageSize = 10) {
        return $this->tradeRequest('getUnfinishedOrdersIgnoreTradeType', ['currency' => $currency, 'pageIndex' => $pageIndex, 'pageSize' => $pageSize]);

    }

    /**
     * 获取用户信息
     * @return array|mixed|stdClass|string
     */
    public function getAccountInfo() {
        return $this->tradeRequest('getAccountInfo');
    }

}

