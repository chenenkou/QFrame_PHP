<?php

/**
 * ȫ��App��
 * Class App
 */
class App {
    // Ӧ��ʵ������
    protected static $_instance = null;
    // �������
    public $request;

    /**
     * �����ʼ��
     */
    protected function __construct() {
        $this->request = new Request();
    }

    /**
     * ��ȡӦ��ʵ��
     * @return App|null
     */
    public static function i() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Ӧ�ó�ʼ��ִ��
     */
    public function run() {
        $this->request->parseUri();
    }
}