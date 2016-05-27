<?php
// https://github.com/samacs/simple_html_dom
class SimpleHtmlDom {

    public static function load($maxFileSize = 0)
    {
        if (!defined('MAX_FILE_SIZE') && !empty($maxFileSize)) {
            define('MAX_FILE_SIZE', $maxFileSize);
        }
        require_once(LIB_PATH . "/Util/SimpleHtmlDom/simple_html_dom.php");
    }
}