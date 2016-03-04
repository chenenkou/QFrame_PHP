<?php
    // 强制显示错误提示
    ini_set("display_errors", "On");error_reporting(E_ALL | E_STRICT);
    require_once(dirname(__FILE__) . "/Frame/Core.php");
    // 应用初始化执行
    App::i()->run();