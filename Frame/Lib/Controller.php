<?php

/**
 * 基类控制器
 * Class Controller
 */
class Controller {

    // 模板数据
    public $assignData = array();

    /**
     * 构造初始化
     */
    public function __construct() {

    }

    /**
     * 模板分配数据
     * @param $key
     * @param $value
     */
    public function assign($key, $value) {
        $this->assignData[$key] = $value;
    }

    /**
     * 模板显示
     * @param string $filePath
     */
    public function display($filePath = '') {
        if (empty($filePath)) {
            $request = App::i()->request;
            $controller = $request->controller;
            $action = $request->action;
            $filePath = "{$controller}/{$action}";
        }

        extract($this->assignData);
        $this->assignData = array();
        require(CORE_PATH . "View/{$filePath}.php");
    }

    /**
     * json方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @return void
     */
    protected function jsonReturn($data) {
        if(func_num_args()>2) {
            $args           =   func_get_args();
            array_shift($args);
            $info           =   array();
            $info['data']   =   $data;
            $info['info']   =   array_shift($args);
            $info['status'] =   array_shift($args);
            $data           =   $info;
        }

        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data));
    }
}