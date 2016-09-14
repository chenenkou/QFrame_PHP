<?php

/**
 * 使用CURL 作为核心操作的HTTP访问类
 *
 * @desc CURL 以稳定、高效、移植性强作为很重要的HTTP协议访问客户端，必须在PHP中安装 CURL 扩展才能使用本功能
 */
class Curl
{
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
     * @var array 302跳转后的url信息
     */
    private $curlInfo = array();

    /**
     * @var string htpasswd账号信息
     */
    private $userPwd = '';

    /**
     * @var string 用户代理
     */
    private $httpUserAgent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36';

    /**
     * 构造函数
     * Curl constructor.
     * @param string $url URL地址
     */
    private function __construct($url)
    {
        $this->uri = $url;
    }

    /**
     * 保证对象不被clone
     */
    private function __clone()
    {
    }

    /**
     * 获取对象唯一实例
     * @param string $url URL地址
     * @return object 返回本对象实例
     */
    public static function getInstance($url = '')
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($url);
        }
        return self::$_instance;
    }

    /**
     * 设置需要发送的HTTP头信息
     *
     * @param array /string 需要设置的头信息，可以是一个 类似 array('Host: example.com', 'Accept-Language: zh-cn') 的头信息数组
     *       或单一的一条类似于 'Host: example.com' 头信息字符串
     * @return void
     */
    public function setHeader($header)
    {
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
     * @param string /array 需要设置的Cookie信息，一个类似于 'name1=value1&name2=value2' 的Cookie字符串信息，
     *         或者是一个 array('name1'=>'value1', 'name2'=>'value2') 的一维数组
     * @return void
     */
    public function setCookie($cookie)
    {
        if (empty($cookie)) {
            return;
        }
        if (is_array($cookie)) {
            $this->cookies = self::makeQuery($cookie, '; ');
        } elseif (is_string($cookie)) {
            $this->cookies = $cookie;
        }
    }

    /**
     * 设置要发送的数据信息
     *
     * 注意：本函数只能调用一次，下次调用会覆盖上一次的设置
     *
     * @param array $vars 设置需要发送的数据信息，一个类似于 array('name1'=>'value1', 'name2'=>'value2') 的一维数组
     * @return void
     */
    public function setVar($vars)
    {
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
    public function setUrl($url)
    {
        if ($url != '') {
            $this->uri = $url;
        }
    }

    /**
     * 发送HTTP GET请求
     *
     * @param string $url 如果初始化对象的时候没有设置或者要设置不同的访问URL，可以传本参数
     * @param array $vars 需要单独返送的GET变量
     * @param array /string 需要设置的头信息，可以是一个 类似 array('Host: example.com', 'Accept-Language: zh-cn') 的头信息数组
     *         或单一的一条类似于 'Host: example.com' 头信息字符串
     * @param string /array 需要设置的Cookie信息，一个类似于 'name1=value1&name2=value2' 的Cookie字符串信息，
     *         或者是一个 array('name1'=>'value1', 'name2'=>'value2') 的一维数组
     * @param int $timeout 连接对方服务器访问超时时间，单位为秒
     * @param array $options 当前操作类一些特殊的属性设置
     * @return mixed
     */
    public function get($url = '', $vars = array(), $header = array(), $cookie = '', $timeout = 5, $options = array())
    {
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
     * @param array /string 需要设置的头信息，可以是一个 类似 array('Host: example.com', 'Accept-Language: zh-cn') 的头信息数组
     *         或单一的一条类似于 'Host: example.com' 头信息字符串
     * @param string /array 需要设置的Cookie信息，一个类似于 'name1=value1&name2=value2' 的Cookie字符串信息，
     *         或者是一个 array('name1'=>'value1', 'name2'=>'value2') 的一维数组
     * @param int $timeout 连接对方服务器访问超时时间，单位为秒
     * @param array $options 当前操作类一些特殊的属性设置
     * @return mixed
     */
    public function post($url = '', $vars = array(), $header = array(), $cookie = '', $timeout = 5, $options = array())
    {
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
     * @param int $timeout 连接对方服务器访问超时时间，单位为秒
     * @param array $options 当前操作类一些特殊的属性设置
     * @return mixed
     * @throws Exception
     */
    public function send($method = 'GET', $timeout = 5, $options = array())
    {
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
            $query = self::makeQuery($this->vars);
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
        $this->curlInfo = $data["curlInfo"];
        return $data['curlRet'];
    }

    /**
     * 获取跳转后的url信息
     * @return array
     */
    public function getCurlInfo()
    {
        return $this->curlInfo;
    }

    /**
     * 生成一个供Cookie或HTTP GET Query的字符串
     *
     * @param array $data 需要生产的数据数组，必须是 Name => Value 结构
     * @param string $sep 两个变量值之间分割的字符，缺省是 &
     * @return string 返回生成好的Cookie查询字符串
     */
    public static function makeQuery($data, $sep = '&')
    {
        $encoded = '';
        while (list($k, $v) = each($data)) {
            $encoded .= ($encoded ? "$sep" : "");
            $encoded .= rawurlencode($k) . "=" . rawurlencode($v);
        }
        return $encoded;
    }

    /**
     * 远程下载
     * @param string $remote 远程图片地址
     * @param string $local 本地保存的地址
     * @param string $cookie cookie地址 可选参数由
     * 于某些网站是需要cookie才能下载网站上的图片的
     * 所以需要加上cookie
     * @return void
     */
    public function reutersload($remote, $local, $cookie = '')
    {
        $cp = curl_init($remote);
        $fp = fopen($local, "w");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        if ($cookie != '') {
            curl_setopt($cp, CURLOPT_COOKIEFILE, $cookie);
        }
        curl_exec($cp);
        curl_close($cp);
        fclose($fp);
    }

    /**
     * 远程下载 - reutersload 别名
     * @param string $remote 远程文件名
     * @param string $local 本地保存文件名
     * @param string $cookie
     */
    public function curlDownload($remote, $local, $cookie = '')
    {
        $this->reutersload($remote, $local, $cookie);
    }

    /**
     * 设置htpasswd账号信息
     * @param string $userPwd htpasswd账号信息
     */
    public function setUserPwd($userPwd = '')
    {
        $this->userPwd = $userPwd;
    }

    /**
     * get 方式获取访问指定地址
     * @param string $url 要访问的地址
     * @param string $cookie cookie的存放地址,没有则不发送cookie
     * @return string curl_exec()获取的信息
     * @author andy
     */
    public function get_m($url, $cookie = '')
    {
        // 初始化一个cURL会话
        $curl = curl_init($url);
        //模拟用户使用的浏览器，在HTTP请求中包含一个”user-agent”头的字符串。
        curl_setopt($curl, CURLOPT_USERAGENT, empty($_SERVER['HTTP_USER_AGENT']) ? $this->httpUserAgent : $_SERVER['HTTP_USER_AGENT'] );
        // 不显示header信息
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 使用htpasswd账号信息
        if ($this->userPwd) curl_setopt($curl, CURLOPT_USERPWD, $this->userPwd);
        // 使用自动跳转
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        if (!empty($cookie)) {
            // 包含cookie数据的文件名，cookie文件的格式可以是Netscape格式，或者只是纯HTTP头部信息存入文件。
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
        }
        // 自动设置Referer
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        // 执行一个curl会话
        $result = curl_exec($curl);
        // 服务器返回的状态
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // 关闭curl会话
        curl_close($curl);
        return ($httpCode === 200) ? $result : false;
    }

    /**
     * get 方式获取访问指定地址
     * @param string $url 要访问的地址
     * @param string $cookie cookie的存放地址,没有则不发送cookie
     * @return string curl_exec()获取的信息
     * @author andy
     */
    public function methodGET($url, $cookie = '')
    {
        return $this->get_m($url, $cookie);
    }

    /**
     * post 方式模拟请求指定地址
     * @param string $url 请求的指定地址
     * @param array $params 请求所带的
     * @param string $cookie cookie存放地址
     * @return string curl_exec()获取的信息
     * @author andy
     */
    public function post_m($url, $params, $cookie = '')
    {
        $curl = curl_init($url);
        $curl_version = curl_version();
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, ($curl_version['version'] >= '7.28.1') ? 2 : 1);
        //模拟用户使用的浏览器，在HTTP请求中包含一个”user-agent”头的字符串。
        curl_setopt($curl, CURLOPT_USERAGENT, empty($_SERVER['HTTP_USER_AGENT']) ? $this->httpUserAgent : $_SERVER['HTTP_USER_AGENT'] );
        //发送一个常规的POST请求，类型为：application/x-www-form-urlencoded，就像表单提交的一样。
        curl_setopt($curl, CURLOPT_POST, 1);
        // 将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 使用htpasswd账号信息
        if ($this->userPwd) curl_setopt($curl, CURLOPT_USERPWD, $this->userPwd);
        // 使用自动跳转
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        // 自动设置Referer
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        // Cookie地址
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
        // 全部数据使用HTTP协议中的"POST"操作来发送。要发送文件，
        // 在文件名前面加上@前缀并使用完整路径。这个参数可以通过urlencoded后的字符串
        // 类似'para1=val1¶2=val2&...'或使用一个以字段名为键值，字段数据为值的数组
        // 如果value是一个数组，Content-Type头将会被设置成multipart/form-data。
        if (is_array($params) || is_object($params)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        }
        // 执行一个curl会话
        $result = curl_exec($curl);
        // 服务器返回的状态
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // 关闭curl会话
        return ($httpCode === 200) ? $result : false;
    }

    /**
     * post 方式模拟请求指定地址
     * @param string $url 请求的指定地址
     * @param array $params 请求所带的
     * @param string $cookie cookie存放地址
     * @return string curl_exec()获取的信息
     * @author andy
     */
    public function methodPOST($url, $params, $cookie = '')
    {
        return $this->post_m($url, $params, $cookie);
    }
}