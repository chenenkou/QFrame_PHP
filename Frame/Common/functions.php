<?php
/**
 * 公用函数
 */

/**
 * 文件保存记录调试
 * @param array $arr 数组源
 * @param string $str 记录说明文字
 */
function fpc($arr, $str = "")
{
    static $z = 0; //函数脚本执行次数
    $z++;
    if ($str != "") {
        $str .= "------";
    }
    if (is_array($arr)) {
        $val = json_encode($arr);
    } else {
        $val = $arr;
    }
    $filename = curfilename();
    $path = "{$filename}_log";

    L($path, "[{$z}]" . $str . date("Y-m-d H:i:s") . "\n" . $val . "\n");
}

/**
 * 获取当前文件的名称
 * @return string
 */
function curfilename()
{
    $f = basename($_SERVER['PHP_SELF']);
    $n = strrpos($f, '.');
    return substr($f, 0, $n);
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $strict = true)
{
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
    } else
        return $output;
}

/**
 * 简易打印输出
 * @param $arr
 */
function p($arr)
{
    dump($arr, 1, "<pre>", 0);
}

/**
 * 打印数据并换行
 * @param mixed $data
 */
function pL($data)
{
    echo print_r($data, true) . "\n";
}

/**
 * 去除代码中的空白和注释
 * @param string $content 代码内容
 * @return string
 */
function strip_whitespace($content)
{
    $stripStr = '';
    //分析php源码
    $tokens = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for ($k = $i + 1; $k < $j; $k++) {
                        if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if ($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time = 0, $msg = '', $continueCode = false)
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        if (!$continueCode) {
            exit();
        }
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

/**
 * 发送邮件
 * @param $body 邮件内容
 * @param string $subject 邮件主题
 */
function sendMail($body, $subject = '第三方激活回调错误报警')
{
    $mail = new Mail();
    $mail->sendSmtpMail(C('MAIL_SEND_TO'), $subject, $body);
    exit("\n");
}

/**
 * 简单异戒加密
 * @param $info 需要加密的字符串
 * @param $key 加密的key
 * @return string
 */
function simpleXor($info, $key)
{
    $result = '';
    $keylen = strlen($key);
    for ($i = 0; $i < strlen($info); $i++) {
        $k = $i % $keylen;
        $result .= $info[$i] ^ $key[$k];
    }
    return $result;
}

/**
 * 断开客户端连接
 */
function offClient()
{
    $size = ob_get_length();
    header("Content-Length: $size"); //告诉浏览器数据长度,浏览器接收到此长度数据后就不再接收数据
    //header("Connection: Close"); //告诉浏览器关闭当前连接,即为短连接
    ob_end_flush();
    flush();
}

/**
 * 删除数组中的指定的key
 * @param $arr
 * @param $keys
 */
function unset_keys(&$arr, $keys)
{
    foreach ($keys as $v) {
        unset($arr[$v]);
    }
}

////////////////////////////////////////////////////////////////
//  数组函数库
////////////////////////////////////////////////////////////////

/**
 * 排序数组
 * @param array $arr 数组源
 * @param string $keys 键值下标
 * @param string $type 倒序或倒序
 * @return array
 */
function array_sort($arr, $keys, $type = 'desc')
{
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ($type == 'desc') {
        arsort($keysvalue);
    } else {
        asort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
        $new_array[] = $arr[$k];
    }
    return $new_array;
}

/**
 * 两个二维数组合并
 * @param array $a      主数组
 * @param array $b      次数组
 * @param string $k     相同的下标键值
 * @param bool $replace 是否替换
 * @param string $new_k 否替换新键值
 * @return array
 */
function array_merge_by_key(&$a, &$b, $k = 'id', $replace = false, $new_k = '')
{
    $c = array();
    foreach ($a as $e) $c[$e[$k]] = $e;
    if ($replace) {
        foreach ($b as $e) $c[$e[$k]] = isset($c[$e[$k]]) ? $c[$e[$k]] + $e : $e;
    } else {
        foreach ($b as $e) $c[$e[$k]] = isset($c[$e[$k]]) ? array_merge($c[$e[$k]], $e) : $e;
    }
    if ($new_k) {
        foreach ($c as $key => $value) {
            $c[$key][$new_k] = $value[$k];
            unset($c[$key][$k]);
        }
    }
    return $c;
}

/**
 * 两个数组根据相同键值组合
 * @param  array $arr1 主数组
 * @param  array $arr2 次数组
 * @return array $c    合并后的数组
 */
function array_merge_combine($arr1, $arr2)
{
    $c = array();
    $i = 0;
    foreach ($arr1[0] as $k => $v) {
        foreach ($arr2[0] as $kk => $vv) {
            if ($v[$arr1[1]] == $vv[$arr2[1]]) {
                $c[$i] = $v + $vv;
                if ($arr1[1] != $arr2[1]) {
                    unset($c[$i][$arr2[1]]);
                }
                $i++;
            }
        }
    }
    return $c;
}

/**
 * 二维数组中的两个值替换
 * @param  array $array  需要处理的数组
 * @param  string $k1    键名1
 * @param  string $k2    键名2
 * @param  bool $replace 是否删除旧键名的值
 * @return array $arr    新数组
 */
function array_value_replace(&$array, $k1, $k2, $replace = true)
{
    $arr = $array;
    foreach ($arr as $k => $v) {
        $arr[$k]["{$k1}_old"] = $v[$k1];
        unset($arr[$k][$k1]);
        $arr[$k][$k1] = $v[$k2];
        if ($replace) {
            $arr[$k][$k2] = $arr[$k]["{$k1}_old"];
            unset($arr[$k]["{$k1}_old"]);
        } else {
            unset($arr[$k][$k2]);
        }
    }
    return $arr;
}

/**
 * 将二维数组中外层下标替换为内层的某个数值重建数组
 * @param array $arr     需要转换的二维数组
 * @param string $minKey 内层数组中用来作为副本的下标
 * @return array
 */
function array_rebuild_by_key($arr, $minKey)
{
    $newArr = array();
    foreach ($arr as $k => $v) {
        $newArr[$v[$minKey]] = $v;
    }
    return $newArr;
}

?>