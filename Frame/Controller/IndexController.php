<?php

/**
 * 默认控制器
 * Class IndexController
 */
class IndexController extends Controller {

    /**
     * 默认方法
     */
    public function index() {
        $name = isset($_GET['name']) ? $_GET['name'] : "World";
        $this->assign('name', $name);
        $this->display();
    }
}