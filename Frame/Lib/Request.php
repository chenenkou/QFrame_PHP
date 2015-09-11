<?php

/**
 * 应用请求对象类
 * Class Request
 */
class Request {
    // 请求的uri
    public $uri;
    // 请求脚本的目录
    public $_scriptDir;
    // 控制器类名
    public $controllerClassName;
    // 请求的控制器
    public $controller;
    // 请求的方法
    public $action;
    // 应用项目基类url
    public $baseUrl;
    // 应用项目host
    public $host;

    /**
     * 构造初始化
     */
    public function __contruct() {

    }

    /**
     * 处理请求
     */
    public function  parseUri() {
        // 设置入口脚本文件目录
        $this->setScriptDir();
        // 设置uri
        $this->setUri();
        // 解析url
        $this->analysisUri();
        // 根据控制器文件实例化方法
        $this->instantiateCA();

    }

    /**
     * 设置入口脚本文件目录
     */
    public function setScriptDir() {
        $script_name = $_SERVER['SCRIPT_NAME'];
        $script_dir = strlen(dirname($script_name)) > 1 ? (dirname($script_name) . "/") : '/';
        $this->_scriptDir = $script_dir;
    }

    /**
     * 设置uri
     */
    public function setUri() {
        $this->uri = $_SERVER['REQUEST_URI'];
        if ($this->_scriptDir == $this->uri) {
            $this->uri = $_SERVER['SCRIPT_NAME'] . "/Index/index";
        }

        // 获取服务器host
        $host = ($_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] : "http") . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
        $this->host = $host;
    }

    /**
     * 解析url
     */
    public function analysisUri () {
        $pattern = '/^('. str_replace("/", "\/", $this->_scriptDir) .')(index.php\/)?([\w\/]+[\w])[\/]?[\?]?([\w\=\-]*)/';
        $subject = $this->uri;
        preg_match($pattern, $subject, $matches);

        $baseUrl = $matches[1];

        $parseUrl = explode('/', $matches[3]);

        $action = array_pop($parseUrl);

        foreach ($parseUrl as $key => &$value) {
            $value = ucfirst($value);
        }

        $controllerClassName = end($parseUrl) . 'Controller';
        $controller = implode('/', $parseUrl);

        $this->baseUrl = $baseUrl;
        $this->controllerClassName = $controllerClassName;
        $this->controller = $controller;
        $this->action = $action;
    }

    /**
     * 根据控制器文件实例化方法
     */
    public function instantiateCA() {
        $controllerClassName = $this->controllerClassName;
        $controller = $this->controller;
        $action = $this->action;

        // 检测控制器是否存在
        $controllerFile = CORE_PATH . 'Controller/' . $controller . 'Controller.php';
        if (!file_exists($controllerFile)) {
            throw_404();
        }
        require_once($controllerFile);

        // 检测方法是否存在
        $ControllerObject = new $controllerClassName;
        if (!method_exists($ControllerObject, $action)) {
            throw_404();
        }

        // 检测方法是否为公有方法
        $method = new ReflectionMethod($controllerClassName, $action);
        if (!($method->isPublic() && !$method->isStatic())) {
            throw_404();
        }

        $ControllerObject->$action();
    }
}