<?php

/**
 * 自动加载类
 * User: cek
 * Date: 16/3/5
 * Time: 上午12:08
 */

QAutoloader::Register();

class QAutoloader
{
    /**
     * 注册自动装卸机SPL
     */
    public static function Register() {
        if (function_exists('__autoload')) {
            // 注册任何现有的自动装卸机与SPL函数,所以我们没有任何冲突
            spl_autoload_register('__autoload');
        }
        // 在SPL注册自己的
        return spl_autoload_register(array('QAutoloader', 'Load'));
    }

    /**
     * 自动装载类识别的名字
     * @param string $pClassName 加载对象的名称
     * @return bool
     */
    public static function Load($pClassName) {
        if (class_exists($pClassName,FALSE))
            return FALSE;

        $dirNames = C('AUTOLOAD_CLASS_DIRS');
        if ( empty($dirNames) )
            return true;

        foreach ($dirNames as $dirName) {
            $pClassFilePath = CORE_PATH . $dirName . DIRECTORY_SEPARATOR .$pClassName.".php";

            // 不能加载,跳过
            if ((file_exists($pClassFilePath) === FALSE) || (is_readable($pClassFilePath) === FALSE))
                continue;

            require_once($pClassFilePath);
            return true;
        }
    }
}