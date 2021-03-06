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
 * @param string $body 邮件内容
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
 * @param string $info 需要加密的字符串
 * @param string $key 加密的key
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

////////////////////////////////////////////////////////////////
//  数组函数库
////////////////////////////////////////////////////////////////

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
 * @param array $a 主数组
 * @param array $b 次数组
 * @param string $k 相同的下标键值
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
 * @param  array $array 需要处理的数组
 * @param  string $k1 键名1
 * @param  string $k2 键名2
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
 * @param array $arr 需要转换的二维数组
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

/**
 * 自定义二维数组去重复
 * @param array $arr1 数组1
 * @param array $arr2 数组2
 * @return array       新数组
 */
function array_diff_u($arr1, $arr2)
{
    foreach ($arr2 as $k => $v) {
        if ($arr1[$k] == $v) {
            unset($arr1[$k]);
        }
    }
    return $arr1;
}

/**
 * 清除一个数组中指定下标的值
 * @param array $arr 指定数组
 * @param string $key 指定下标名称
 * @return array
 */
function array_remove_key($arr, $key)
{
    foreach ($arr as $k => $v) {
        if ($k == $key) {
            unset($arr[$k]);
            break;
        }
    }
    return $arr;
}

////////////////////////////////////////////////////////////////
//  文件函数库
////////////////////////////////////////////////////////////////

/**
 * 读取本地文件提供下载
 * @param $file string 本地文件名
 */
function read_file_down($file)
{
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }
}

////////////////////////////////////////////////////////////////
//  时间函数库
////////////////////////////////////////////////////////////////

/**
 * 获取日初和末的时间戳
 * @param $time int
 * @return array
 */
function get_day_time($time = 0)
{
    if (empty($time))
        $t = time();
    else
        $t = $time;

    $start = mktime(0, 0, 0, date("m", $t), date("d", $t), date("Y", $t));
    $end = mktime(23, 59, 59, date("m", $t), date("d", $t), date("Y", $t));

    return array($start, $end);
}

/**
 * 获取月初和末的时间戳
 * @param $time int
 * @return array
 */
function get_month_time($time = 0)
{
    if (empty($time))
        $t = time();
    else
        $t = $time;

    $start = strtotime(date('Y-m', $t));
    $end = strtotime(date('Y-m-t', $t)) + 86400 - 1;

    return array($start, $end);
}

/**
 * 模板截取字符串
 * @param $str
 * @param int $start
 * @param $length
 * @param string $charset
 * @param bool $suffix
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr")) {
        if ($suffix)
            return mb_substr($str, $start, $length, $charset) . "...";
        else
            return mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        if ($suffix)
            return iconv_substr($str, $start, $length, $charset) . "...";
        else
            return iconv_substr($str, $start, $length, $charset);
    }
    $re['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef]
                  [x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
    $re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
    $re['gbk'] = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
    $re['big5'] = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("", array_slice($match[0], $start, $length));
    if ($suffix) return $slice . "…";
    return $slice;
}

/**
 * windows下cmd中调试数据
 * @param array $data 数据
 */
function cmdDebug($data)
{
    echo iconv('UTF-8', 'GBK', print_r($data, true)) . "\n";
    exit();
}

/**
 * 修改二维数组为value值为$key的数组
 * @param array $arr 数组
 * @param string $key 指定二维中的$key为value
 * @return array
 */
function change_array_dimension($arr, $key)
{
    $new_arr = array();
    foreach ($arr as $k => $item) {
        $new_arr[$k] = $item[$key];
    }
    return $new_arr;
}

/**
 * 数组下标根据子数组下标值重建
 * @param array $arr 二维数组
 * @param string $field 子数组下标名
 * @return array 新二维数组
 */
function array_key_rebuild_field($arr, $field = 'id')
{
    $newArr = array();
    if (empty($arr))
        return $newArr;
    foreach ($arr as $item) {
        if (!isset($item[$field]))
            continue;
        $newArr[$item[$field]] = $item;
    }

    return $newArr;
}

/**
 * object 转 array
 */
function object_to_array($obj)
{
    $arr = array();
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    foreach ($_arr as $key => $val) {
        $val = (is_array($val)) || is_object($val) ? object_to_array($val) : $val;
        $arr[$key] = $val;
    }
    return $arr;
}


?>