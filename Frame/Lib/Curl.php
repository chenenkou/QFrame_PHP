<?php
/**
 * 使用CURL 作为核心操作的HTTP访问类
 *
 * @desc CURL 以稳定、高效、移植性强作为很重要的HTTP协议访问客户端，必须在PHP中安装 CURL 扩展才能使用本功能
 */
class Curl {
    /**
     * @var object 对象单例
     */
    static $_instance = NULL;
    /**
     * @var string 需要发送的cookie信息
     */
    private $cookies = '';
    /**
     * @var array 需要发送的头信息
     */
    private $header = array();
    /**
     * @var string 需要访问的URL地址
     */
    private $uri = '';
    /**
     * @var array 需要发送的数据
     */
    private $vars = array();
    /**
     * 构造函数
     *
     * @param string $configFile 配置文件路径
     */
    private function __construct($url) {
        $this->uri = $url;
    }
    /**
     * 保证对象不被clone
     */
    private function __clone() {
    }
    /**
     * 获取对象唯一实例
     *
     * @param string $configFile 配置文件路径
     * @return object 返回本对象实例
     */
    public static function getInstance($url = '') {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($url);
        }
        return self::$_instance;
    }
    /**
     * 设置需要发送的HTTP头信息
     *
     * @param array/string 需要设置的头信息，可以是一个 类似 array('Host: example.com', 'Accept-Language: zh-cn') 的头信息数组
     *       或单一的一条类似于 'Host: example.com' 头信息字符串
     * @return void
     */
    public function setHeader($header) {
        if (empty($header)) {
            return;
        }
        if (is_array($header)) {
            foreach ($header as $k => $v) {
                $this->header[] = is_numeric($k) ? trim($v) : (trim($k) . ": " . trim($v));
            }
        } elseif (is_string($header)) {
            $this->header[] = $header;
        }
    }
    /**
     * 设置Cookie头信息
     *
     * 注意：本函数只能调用一次，下次调用会覆盖上一次的设置
     *
     * @param string/array 需要设置的Cookie信息，一个类似于 'name1=value1&name2=value2' 的Cookie字符串信息，
     *         或者是一个 array('name1'=>'value1', 'name2'=>'value2') 的一维数组
     * @return void
     */
    public function setCookie($cookie) {
        if (empty($cookie)) {
            return;
        }
        if (is_array($cookie)) {
            $this->cookies = Http::makeQuery($cookie, '; ');
        } elseif (is_string($cookie)) {
            $this->cookies = $cookie;
        }
    }
    /**
     * 设置要发送的数据信息
     *
     * 注意：本函数只能调用一次，下次调用会覆盖上一次的设置
     *
     * @param array 设置需要发送的数据信息，一个类似于 array('name1'=>'value1', 'name2'=>'value2') 的一维数组
     * @return void
     */
    public function setVar($vars) {
        if (empty($vars)) {
            return;
        }
        $this->vars = $vars;
    }
    /**
     * 设置要请求的URL地址
     *
     * @param string $url 需要设置的URL地址
     * @return void
     */
    public function setUrl($url) {
        if ($url != '') {
            $this->uri = $url;
        }
    }
    /**
     * 发送HTTP GET请求
     *
     * @param string $url 如果初始化对象的时候没有设置或者要设置不同的访问URL，可以传本参数
     * @param array $vars 需要单独返送的GET变量
     * @param array/string 需要设置的头信息，可以是一个 类似 array('Host: example.com', 'Accept-Language: zh-cn') 的头信息数组
     *         或单一的一条类似于 'Host: example.com' 头信息字符串
     * @param string/array 需要设置的Cookie信息，一个类似于 'name1=value1&name2=value2' 的Cookie字符串信息，
     *         或者是一个 array('name1'=>'value1', 'name2'=>'value2') 的一维数组
     * @param int $timeout 连接对方服务器访问超时时间，单位为秒
     * @param array $options 当前操作类一些特殊的属性设置
     * @return unknown
     */
    public function get($url = '', $vars = array(), $header = array(), $cookie = '', $timeout = 5, $options = array()) {
        $this->setUrl($url);
        $this->setHeader($header);
        $this->setCookie($cookie);
        $this->setVar($vars);
        return $this->send('GET', $timeout);
    }
    /**
     * 发送HTTP POST请求
     *
     * @param string $url 如果初始化对象的时候没有设置或者要设置不同的访问URL，可以传本参数
     * @param array $vars 需要单独返送的GET变量
     * @param array/string 需要设置的头信息，可以是一个 类似 array('Host: example.com', 'Accept-Language: zh-cn') 的头信息数组
     *         或单一的一条类似于 'Host: example.com' 头信息字符串
     * @param string/array 需要设置的Cookie信息，一个类似于 'name1=value1&name2=value2' 的Cookie字符串信息，
     *         或者是一个 array('name1'=>'value1', 'name2'=>'value2') 的一维数组
     * @param int $timeout 连接对方服务器访问超时时间，单位为秒
     * @param array $options 当前操作类一些特殊的属性设置
     * @return unknown
     */
    public function post($url = '', $vars = array(), $header = array(), $cookie = '', $timeout = 5, $options = array()) {
        $this->setUrl($url);
        $this->setHeader($header);
        $this->setCookie($cookie);
        $this->setVar($vars);
        return $this->send('POST', $timeout);
    }
    /**
     * 发送HTTP请求核心函数
     *
     * @param string $method 使用GET还是POST方式访问
     * @param array $vars 需要另外附加发送的GET/POST数据
     * @param int $timeout 连接对方服务器访问超时时间，单位为秒
     * @param array $options 当前操作类一些特殊的属性设置
     * @return string 返回服务器端读取的返回数据
     */
    public function send($method = 'GET', $timeout = 5, $options = array()) {
        //处理参数是否为空
        if ($this->uri == '') {
            throw new Exception(__CLASS__ . ": Access url is empty");
        }
        //初始化CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        //设置特殊属性
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //处理GET请求参数
        if ($method == 'GET' && !empty($this->vars)) {
            $query = Http::makeQuery($this->vars);
            $parse = parse_url($this->uri);
            $sep = isset($parse['query']) ? '&' : '?';
            $this->uri .= $sep . $query;
        }
        //处理POST请求数据
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->vars);
        }
        //设置cookie信息
        if (!empty($this->cookies)) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);
        }
        //设置HTTP缺省头
        if (empty($this->header)) {
            $this->header = array(
                'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; InfoPath.1)',
                //'Accept-Language: zh-cn',
                //'Cache-Control: no-cache',
            );
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        //发送请求读取输数据
        curl_setopt($ch, CURLOPT_URL, $this->uri);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = array();
        $data["curlRet"] = curl_exec($ch);
        $data["curlInfo"] = curl_getinfo($ch);
        if (($err = curl_error($ch))) {
            curl_close($ch);
            throw new Exception(__CLASS__ . " error: " . $err);
        }
        curl_close($ch);
        return $data['curlRet'];
    }
}