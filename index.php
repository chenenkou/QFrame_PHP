<?php
    // 强制显示错误提示
    //ini_set("display_errors", "On");error_reporting(E_ALL | E_STRICT);

    header("Content-type: text/html; charset=utf-8");

    define('FOLDER_NAME', 'Frame');

    require_once(dirname(__FILE__) . "/" . FOLDER_NAME . "/Core.php");
