<?php
    require_once(dirname(__FILE__) . "/Frame/Core.php");
    // 命令行应用初始化执行
    Cmd::i()->run($argv);