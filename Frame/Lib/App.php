<?php

/**
 * 全局App类
 * Class App
 */
class App {
    // 应用实例对象
    protected static $_instance = null;
    // 请求对象
    public $request;

    /**
     * 构造初始化
     */
    protected function __construct() {
        $this->request = new Request();
    }

    /**
     * 获取应用实体
     * @return App|null
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
    public function run() {
        $this->request->parseUri();
    }
}