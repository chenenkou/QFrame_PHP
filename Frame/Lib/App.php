<?php

/**
 * 全局应用对象类
 * Class App
 */
class App {
    // 应用实例
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
     * 单例对象
     * @return App|null
     */
    public static function instance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 初始化应用
     */
    public function run() {
        $this->request->parseUri();
    }
}