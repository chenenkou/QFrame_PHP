<?php
    header("Content-type: text/html; charset=utf-8");
    define('Q_VERSION', '2.3.150327'); // 版本号
    if (!defined('ROOT')) {
        define('ROOT', './'); // 根目录常量设置检测
    }
    define('FOLDER_NAME', 'Frame'); // 框架文件夹名称
    define('CORE_PATH', ROOT. FOLDER_NAME .'/'); // 框架文件夹路径
    define('DATA_PATH', ROOT. FOLDER_NAME .'/Data/'); // 缓存数据文件夹路径
    define('LIB_PATH', ROOT. FOLDER_NAME .'/Lib/'); // 类库文件夹路径
    require_once(CORE_PATH."Common/common.php");      // 载入基础函数
    require_once(CORE_PATH."Common/functions.php");      // 载入公用函数
    // 部分设置项
    date_default_timezone_set('PRC'); // 设置为中国时区