<?php
/**
 * http请求封装
 * @author 夜雨 yeyupl@qq.com
 */

namespace ZB;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;

class http {

    public $cookieFile;
    public $client;
    protected $cookieJar;
    private $options = [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2',
            'Connection' => 'keep-alive'
        ],
        'timeout' => 10,
        'verify' => false
    ];

    /**
     * http constructor.
     * http constructor.
     * @param $cookieFile
     * @param array $options
     */
    public function __construct($cookieFile = '', $options = []) {
        if ($cookieFile) {
            $this->cookieFile = $cookieFile;
            $this->cookieJar = new FileCookieJar($cookieFile, true);
            $this->client = new Client(['cookies' => $this->cookieJar]);
        } else {
            $this->client = new Client();
        }
        $this->setOptions($options);
    }

    /**
     * set options
     * @param array $options
     */
    public function setOptions(array $options) {
        if (is_array($options) && $options) {
            $this->options = $this->array_join_merge($this->options, $options);
        }
    }

    /**
     * GET请求
     * @param $url
     * @param array $options
     * @param bool $decode
     * @return array|mixed|\stdClass|string
     */
    public function get($url, array $options = [], $decode = false) {
        $content = $this->request($url, 'GET', $options);
        return $decode ? json_decode($content, true) : $content;
    }

    /**
     * POST请求
     * @param $url
     * @param array $query
     * @param bool $decode
     * @return array|mixed|\stdClass|string
     */
    public function post($url, $query = [], $decode = false) {
        $key = is_array($query) ? 'form_params' : 'body';

        $content = $this->request($url, 'POST', [$key => $query]);

        return $decode ? json_decode($content, true) : $content;
    }

    /**
     * POST JSON请求
     * @param $url
     * @param array $params
     * @param bool $decode
     * @param array $extra
     * @return array|mixed|\stdClass|string
     */
    public function json($url, $params = [], $decode = false, $extra = []) {

        $params = array_merge(['json' => $params], $extra);

        $content = $this->request($url, 'POST', $params);

        return $decode ? json_decode($content, true) : $content;
    }


    /**
     * HTTP请求
     * @param $url
     * @param string $method
     * @param array $options
     * @param bool $retry
     * @return string
     */
    public function request($url, $method = 'GET', $options = [], $retry = false) {
        try {
            $options = $this->array_join_merge($this->options, $options);

            $response = $this->client->request($method, $url, $options);

            $this->cookieJar && $this->cookieJar->save($this->cookieFile);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            if (!$retry) {
                return $this->request($url, $method, $options, true);
            }
            return false;
        }
    }

    /**
     * 合并多维数组
     * @param $arr1
     * @param $arr2
     * @return array
     */
    private function array_join_merge($arr1, $arr2) {
        if (is_array($arr1) && is_array($arr2)) {
            $array = [];
            $keys = array_merge(array_keys($arr1), array_keys($arr2));
            foreach ($keys as $key) {
                $arr1[$key] = (isset($arr1[$key]) ? $arr1[$key] : '');
                $arr2[$key] = (isset($arr2[$key]) ? $arr2[$key] : '');
                $array[$key] = $this->array_join_merge($arr1[$key], $arr2[$key]);
            }
            return $array;
        } else {
            return $arr2 ? $arr2 : $arr1;
        }
    }
}
