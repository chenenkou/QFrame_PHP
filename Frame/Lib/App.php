<?php

/**
 * ȫ��Ӧ�ö�����
 * Class App
 */
class App {
    // Ӧ��ʵ��
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
     * ��������
     * @return App|null
     */
    public static function instance() {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * ��ʼ��Ӧ��
     */
    public function run() {
        $this->request->parseUri();
    }
}