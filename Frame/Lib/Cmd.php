<?php

/**
 * 全局Cmd类
 * Class Cmd
 */
class Cmd {
    // 应用实例对象
    protected static $_instance = null;
    // 请求对象
    public $request;

    /**
     * 构造初始化
     */
    protected function __construct() {
        $this->request = new CmdArgv();
    }

    /**
     * 获取应用实体
     * @return Cmd|null
     */
    public static function i() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 应用初始化执行
     */
    public function run($argv) {
        if (strtolower(php_sapi_name()) != 'cli')
            die("Must be on the command line");
        array_shift($argv);
        $this->request->parseArgv($argv);
    }
}