<?php
/**
 * 基础函数库
 */

/**
 * 宏替换
 * @param $subject
 * @param $search_replace
 */
function macroReplace(&$subject, $search_replace) {
    foreach ($search_replace as $search => $replace) {
        $subject = str_replace($search, $replace, $subject);
    }
}

/**
 * 将数据写入文件
 * @param string $fileName 文件名
 * @param string $content 文件内容
 * @param string $fileExt 文件后缀名
 * @param string $filePath 文件存放目录
 * @param int $fileSize 文件大小
 */
function cont2file($fileName, $content, $fileExt, $filePath, $fileSize = 4194304 ) {
    static $n = 0;

    $basePath = CORE_PATH;
    $path = "<FILE_PATH>/<FILE_NAME>.<FILE_EXT>";
    macroReplace($path, array(
        '<FILE_PATH>' => $basePath . $filePath,
        '<FILE_NAME>' => $fileName . "_i{$n}",
        '<FILE_EXT>' => $fileExt,
    ));

    // 防止文件过大默认4M生成新文件
    if (file_exists($path)) {
        clearstatcache(); // 清除文件状态缓存
        $fSize = filesize($path);
        //echo $fSize . "\n";
        if ($fSize >= $fileSize) {
            $n++;
            $path = preg_replace('/\_i\d/', "_i{$n}", $path);
        }
    }

    file_put_contents($path, $content."\n", FILE_APPEND);
}

/**
 * log信息
 * @param $fileName log文件名
 * @param $content log内容
 */
function L($fileName, $content) {
    cont2file($fileName, $content, 'log', 'Log');
}

/**
 * 获取和设置配置参数 支持批量定义
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @param bool $c 更改配置文件
 * @return mixed
 */
function C($name=null, $value=null, $c = false) {
    static $_config = array();
    if(empty($_config)) {
        $confPath = CORE_PATH.'Conf/';
        $_config = array_change_key_case(F('config', '', 1, $confPath));
        if ( file_exists( $confPath . 'main.php') ) {
            $_mainConfig = array_change_key_case(F('main', '', 1, $confPath));
            $_config = array_merge($_config, $_mainConfig);
        }
    }
    // 无参数时获取所有
    if (empty($name)) {
        return $_config;
    }
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value))
                return isset($_config[$name]) ? $_config[$name] : null;
            $_config[$name] = $value;
            if($c) {
                $_config = array_change_key_case($_config, CASE_UPPER);
                F('config', $_config, CORE_PATH.'Conf/');
            }
            return true;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0]   =  strtolower($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
        $_config[$name[0]][$name[1]] = $value;
        if($c) {
            $_config = array_change_key_case($_config, CASE_UPPER);
            F('config', $_config, 1, CORE_PATH.'Conf/');
        }
        return true;
    }
    return null; // 避免非法参数
}

/**
 * D函数用于实例化数据库连接对象
 * @param $string $connection 数据库连接信息 默认DB_CONFIG0
 * @return Model
 */
function D($connection='') {
    if(empty($connection)) {
        $connection = 'DB_CONFIG0';
    }
    $reg = "/\d+$/";
    preg_match($reg, $connection, $arr);
    $k = $arr[0];
    $config = C($connection);
    // 根据连接类型连接数据库
    $connectType = isset($config['connect_type']) ? strtolower($config['connect_type']) : '';
    switch ($connectType) {
        case 'mysqli':
            return DbMysqli::getInstance($k, $config);
            break;
        case 'pdo':
            return DbPdo::getInstance($k, $config);
            break;
        case 'mysql':
            return Db::getInstance($k, $config);
            break;
        default :
            if ( extension_loaded('mysqli') )
                return DbMysqli::getInstance($k, $config);
            if ( class_exists("PDO") )
                return DbPdo::getInstance($k, $config);
            return Db::getInstance($k, $config);
    }
}

/**
 * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
 * @param string $name 缓存名称
 * @param string $value 缓存值
 * @param bool $zip
 * @param string $path 缓存路径
 * @return array|bool|int|mixed|string
 */
function F($name, $value='',$zip=true, $path=DATA_PATH) {
    static $_cache  = array();
    $filename       = $path . $name . '.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // 删除缓存
            return false !== strpos($name,'*')?array_map("unlink", glob($filename)):unlink($filename);
        } else {
            // 缓存数据
            $dir            =   dirname($filename);
            // 目录不存在则创建
            if (!is_dir($dir))
                mkdir($dir,0755,true);
            $_cache[$name]  =   $value;
            if($zip) {
                return file_put_contents($filename, "<?php\treturn " . var_export($value, true) . ";?>");
            } else {
                return file_put_contents($filename, strip_whitespace("<?php\treturn " . var_export($value, true) . ";?>"));
            }
        }
    }
    if (isset($_cache[$name]))
        return $_cache[$name];
    // 获取缓存数据
    if (is_file($filename)) {
        $value          =   include $filename;
        $_cache[$name]  =   $value;
    } else {
        $value          =   false;
    }
    return $value;
}

/**
 * 实例化一个模型文件
 * 即表文件名
 * @param $name 表名称
 * @return mixed
 */
function M($name) {
    static $_model = array();
    if (!isset($_model[$name])) {
        require_once(CORE_PATH . 'Model/' . $name . '.php');
        $_model[$name] = new $name;
    }
    return $_model[$name];
}

/**
 * 终端脚本运行时打印输出
 * @param string $str 输出的内容
 * @param string $m   相关附加内容
 */
function printout($str, $m = '') {
    switch($m) {
        case 's':
            $m = "处理成功";
            break;
        case 'f':
            $m = '处理失败';
            break;
        default:
            $m = '';
            break;
    }
    $str = $str.$m;
    echo $str."\n";
    fpc($str);
}

/**
 * 配合SQL中where处理
 * @param $arr where条件数组
 * @return string
 */
function whereArr2Str($arr) {
    $tmp = array();
    $tmp[] = '1 = 1';
    foreach ($arr as $k=>$v) {
        $tmp[] = $k .' = '. $v;
    }
    return implode(' AND ', $tmp);
}

/**
 * 数组转变成插入语句中的字符
 * @param $data
 * @return array
 */
function arr2InsertSql($data) {
    $arr = array(
        'fields' => array(),
        'values' => array(),
    );
    foreach ($data as $k=>$v) {
        $v = addslashes($v);

        $arr['fields'][] = "`{$k}`";
        $arr['values'][] = "'{$v}'";
    }
    $arr['fields'] = implode(', ', $arr['fields']);
    $arr['values'] = implode(', ', $arr['values']);
    return array($arr['fields'], $arr['values']);
}

/**
 * 二维数组转变成更新语句中的字符
 * @param $data
 * @return array
 */
function doubleArr2InsertSql($data) {
    $fields = array();
    $values = array();
    foreach($data as $item) {
        list($field, $value) = arr2InsertSql($item);
        if (empty($fields)) $fields = $field;
        $values[] = $value;
    }
    $values = "(" . implode("), (", $values) . ")";
    return array($fields, $values);
}

/**
 * 数组转变成更新语句中的字符
 * @param $data
 * @return array
 */
function arr2UpdateSql($data) {
    $arr = array(
        'data' => array(),
        'where' => array(),
    );
    foreach ($data['data'] as $k=>$v) {
        $arr['data'][] = "{$k} = '{$v}'";
    }

    if (!isset($data['where']) || empty($data['where'])) {
        $arr['where'] = "1=1";
    } else {
        foreach ($data['where'] as $k=>$v) {
            $arr['where'][] = "{$k} = '{$v}'";
        }
    }
    $arr['data'] = implode(', ', $arr['data']);
    $arr['where'] = implode(' AND ', $arr['where']);
    return $arr;
}

/**
 * 判断是否是命令行执行
 * @return bool
 */
function is_shell() {
    if (!isset($_SERVER['SHELL'])) {
        return false;
    }
    return true;
}

/**
 * 抛出404
 */
function throw_404() {
    @header("http/1.1 404 not found");
    @header("status: 404 not found");
    echo '<center><h1>404 Not Found</h1></center><hr />';
    exit();
}

/**
 * 移除URL中的指定GET变量
 * @param string $var 要移除的GET变量
 * @param null $url url地址
 * @return mixed|string 移除GET变量后的URL地址
 */
function remove_url_param($var, $url = null) {
    if (is_null($url))
        $url = App::i()->request->uri;
    $url = $url . '&';
    $url = preg_replace(array("/$var=.*?&/", "/&&/"), '', $url);
    return rtrim(rtrim($url, "&"), "?");
}

/**
 * 替换URL中的指定GET变量
 * @param $param 要替换的GET变量
 * @param null $uri
 * @return mixed|null|string
 */
function replace_url_param($param, $uri = null) {
    $paramArr = explode('=', $param);
    $uri = remove_url_param($paramArr[0], $uri);
    $separator = "&";
    if (!strpos($uri, "?"))
        $separator = '?';
    $uri .= "{$separator}{$param}";
    return $uri;
}

/**
 * 驼峰命名转下划线命名
 * @param $str
 * @return string
 */
function hump2underline($str) {
    return strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', $str));
}
