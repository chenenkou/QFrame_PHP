<?php
    // 强制显示错误提示
    //ini_set("display_errors", "On");error_reporting(E_ALL | E_STRICT);

    define('FOLDER_NAME', 'Frame');

    require_once(dirname(__FILE__) . "/" . FOLDER_NAME . "/Core.php");
    // 应用初始化执行
    App::i()->run();