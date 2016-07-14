<?php
    header("Content-type: text/html; charset=utf-8");
    define('Q_VERSION', '3.16.0714'); // 版本号
    if (!defined('ROOT')) {
        define('ROOT', dirname(dirname(__FILE__)) . '/'); // 根目录常量设置检测
    }
    defined('FOLDER_NAME') or define('FOLDER_NAME', 'Frame'); // 框架文件夹名称
    define('CORE_PATH', ROOT. FOLDER_NAME .'/'); // 框架文件夹路径
    define('DATA_PATH', ROOT. FOLDER_NAME .'/Data/'); // 缓存数据文件夹路径
    define('LIB_PATH', ROOT. FOLDER_NAME .'/Lib/'); // 类库文件夹路径
    define('LOG_PATH', ROOT. FOLDER_NAME .'/Log/'); // 日志文件夹路径
    define('VIEW_PATH', ROOT. FOLDER_NAME .'/View/'); // 模板文件夹路径
    require_once(CORE_PATH."Common/common.php");      // 载入基础函数
    require_once(CORE_PATH."Common/functions.php");      // 载入公用函数
    require_once(LIB_PATH . 'QAutoloader.php'); // 自动加载类
    // 部分设置项
    date_default_timezone_set('PRC'); // 设置为中国时区