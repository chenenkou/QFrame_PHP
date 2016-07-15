<?php

/**
 * 应用请求参数类
 * Class Request
 */
class CmdArgv {
    // 请求argv
    public $argv;
    // 控制器类名
    public $controllerClassName;
    // 请求的控制器
    public $controller;
    // 请求的方法
    public $action;
    // 请求参数
    public $params;

    /**
     * 构造初始化
     */
    public function __contruct() {

    }

    /**
     * 处理请求
     */
    public function  parseArgv($argv) {
        // 设置请求argv
        $this->setArgv($argv);
        // 解析argv
        $this->analysisArgv();
        // 根据控制器文件实例化方法
        $this->instantiateCA();

    }

    /**
     * 设置请求参数
     * @param $argv
     */
    public function setArgv($argv) {
        $this->argv = $argv;
    }

    /**
     * 解析argv
     */
    public function analysisArgv() {
        $argv = $this->argv;

        $parseArgv = explode('/', $argv[0]);

        $action = array_pop($parseArgv);

        foreach ($parseArgv as $key => &$value) {
            $value = ucfirst($value);
        }

        $controllerClassName = end($parseArgv) . 'Command';
        $controller = implode('/', $parseArgv);

        array_shift($argv);
        $params = array();
        foreach ($argv as $arg) {
            $pos = strpos($arg, "=");
            $pKey = substr($arg, 0, $pos);
            $pValue = substr($arg, $pos+1);
            if ( empty($pKey) || empty($pValue) )
                die("Params error \n");

            $params[$pKey] = $pValue;
        }

        $this->controllerClassName = $controllerClassName;
        $this->controller = $controller;
        $this->action = $action;
        $this->params = $params;
    }

    /**
     * 根据控制器文件实例化方法
     */
    public function instantiateCA() {
        $controllerClassName = $this->controllerClassName;
        $controller = $this->controller;
        $action = $this->action;

        // 检测控制器是否存在
        $controllerFile = CORE_PATH . 'Command/' . $controller . 'Command.php';
        if (!file_exists($controllerFile)) {
            die("Command not found \n");
        }
        require_once($controllerFile);

        // 检测方法是否存在
        $ControllerObject = new $controllerClassName;
        if (!method_exists($ControllerObject, $action)) {
            die("Action not found \n");
        }

        // 检测方法是否为公有方法
        $method = new ReflectionMethod($controllerClassName, $action);
        if (!($method->isPublic() && !$method->isStatic())) {
            die("Action error \n");
        }

        $paramValues = array();
        $params = $this->params;
        foreach($method->getParameters() as $v) {
            if ( !isset($params[$v->name]) ) {
                break;
            }
            $paramValues[] = $params[$v->name];
        }

        call_user_func_array(array(new $controllerClassName, $action),  $paramValues);
    }

}
