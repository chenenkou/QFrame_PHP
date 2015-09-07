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
 * log信息
 * @param $fileName log文件名
 * @param $content log内容
 */
function L($fileName, $content) {
    // 规定log存放目录
    $path = CORE_PATH . "Log/<FILE_NAME>.log";
    // 宏替换目录名
    macroReplace($path, array('<FILE_NAME>' => $fileName));
    // 存放数据
    file_put_contents($path, $content."\n", FILE_APPEND);
}

/**
 * 自动加载Lib类
 */
function __autoload($class_name) {
    $dirNames = C('AUTOLOAD_CLASS_DIRS');
    foreach ($dirNames as $dirName) {
        $file = CORE_PATH . $dirName . "/".$class_name.".php";
        if (is_file($file)) { require_once($file); break; }
    }
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
        $_config = array_change_key_case(F('config', '', 1, CORE_PATH.'Conf/'));
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
            return;
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
        return;
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
    return Db::getInstance($k, $config);
}

/**
 * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
 * @param string $name 缓存名称
 * @param mixed $value 缓存值
 * @param string $path 缓存路径
 * @return mixed
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
        $arr['fields'][] = $k;
        $arr['values'][] = "'{$v}'";
    }
    $arr['fields'] = implode(', ', $arr['fields']);
    $arr['values'] = implode(', ', $arr['values']);
    return $arr;
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

// 抛出404
function throw_404() {
    @header("http/1.1 404 not found"); 
    @header("status: 404 not found");
    echo '<center><h1>404 Not Found</h1></center><hr />';
    exit();
}