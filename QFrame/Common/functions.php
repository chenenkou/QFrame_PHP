<?php
/**
 * 公用函数
 *
 */
 
/**
 * 排序数组
 * @param array $arr 数组源
 * @param string $keys 键值下标
 * @param string $type 倒序或倒序
 * @return array
 */
function array_sort($arr,$keys,$type='desc'){ 
	$keysvalue = $new_array = array();
	foreach ($arr as $k=>$v){
		$keysvalue[$k] = $v[$keys];
	}
	if($type == 'desc'){
		arsort($keysvalue);
	}else{
		asort($keysvalue);
	}
	reset($keysvalue);
	foreach ($keysvalue as $k=>$v){
		$new_array[] = $arr[$k];
	}
	return $new_array; 
}

/**
 * 文件保存记录
 * @param array $arr 数组源
 * @param string $str 记录说明文字
 */
function fpc($arr,$str=""){
	static $n = 0;
	static $z = 0; //函数脚本执行次数
	$z++;
	if($str!=""){$str .= "------";}
    if(is_array($arr)){
        $val = json_encode($arr);
    }else{
        $val = $arr;
    }
	$filename = curfilename();
	$path = CORE_PATH."Log/{$filename}_log_{$n}.log";
	if(file_exists($path)) {
		clearstatcache();
		$fSize = filesize($path);
	}
	if($fSize>(5000*1024)) { //日志文件大于10M重新生成新文件
		$n++;
		$path = CORE_PATH."Log/{$filename}_log_{$n}.log";
	}
    file_put_contents($path, "[{$z}]".$str.date("Y-m-d H:i:s")."\n".$val."\n\n", FILE_APPEND);
}

/**
 * 获取当前文件的名称
 * @return string
 */
function curfilename() {
	$f = basename($_SERVER['PHP_SELF']);
	$n = strrpos($f, '.');
	return substr($f, 0, $n);
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
 * 自动加载Lib类
 */
function __autoload($class_name) {
	require_once(CORE_PATH."Lib/".$class_name.".php");
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo=true, $label=null, $strict=true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else
        return $output;
}

/**
 * 简易打印输出
 * @param $arr
 */
function p($arr){
    dump($arr, 1, "<pre>", 0);
}

/**
 * 两个二维数组合并
 * @param $a
 * @param $b
 * @param bool $new_k
 * @return array
 */
function mergeByKey(&$a,&$b, $k='id',$replace = false, $new_k = false){
    $c=array();
    foreach($a as $e)	$c[$e[$k]]=$e;
	if($replace) {
		foreach($b as $e)	$c[$e[$k]]=isset($c[$e[$k]])? $c[$e[$k]]+$e : $e;
	} else {
		foreach($b as $e)	$c[$e[$k]]=isset($c[$e[$k]])? array_merge($c[$e[$k]], $e) : $e;
	}	
    if($new_k){
        foreach($c as $k=>$v) {
            $c[$k][$new_k] = $v['id'];
            unset($c[$k]['id']);
        }

    }
    return $c;
}

/**
 * 去除代码中的空白和注释
 * @param string $content 代码内容
 * @return string
 */
function strip_whitespace($content) {
    $stripStr   = '';
    //分析php源码
    $tokens     = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr  .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr  .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for($k = $i+1; $k < $j; $k++) {
                        if(is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr  .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
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
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time=0, $msg='', $continueCode = false) {
    //多行URL地址支持
    $url        = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg    = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        if (!$continueCode) { exit();}
    } else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
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
 * 发送邮件
 * @param $body 邮件内容
 * @param string $subject 邮件主题
 */
function sendMail($body, $subject = '第三方激活回调错误报警') {
    $mail = new Mail();
    $mail->sendSmtpMail(C('MAIL_SEND_TO'), $subject, $body);
    die();
}

?>