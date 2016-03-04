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
     * Register the Autoloader with SPL
     *
     */
    public static function Register() {
        if (function_exists('__autoload')) {
            //    Register any existing autoloader function with SPL, so we don't get any clashes
            spl_autoload_register('__autoload');
        }
        //    Register ourselves with SPL
        return spl_autoload_register(array('QAutoloader', 'Load'));
    }   //    function Register()


    /**
     * Autoload a class identified by name
     *
     * @param    string    $pClassName        Name of the object to load
     */
    public static function Load($pClassName) {
        if (class_exists($pClassName,FALSE))
            return FALSE;

        $dirNames = C('AUTOLOAD_CLASS_DIRS');
        foreach ($dirNames as $dirName) {
            $pClassFilePath = CORE_PATH . $dirName . DIRECTORY_SEPARATOR .$pClassName.".php";

            if ((file_exists($pClassFilePath) === FALSE) || (is_readable($pClassFilePath) === FALSE)) // Can't load
                return FALSE;

            require_once($pClassFilePath);
        }
    }   //    function Load()
}