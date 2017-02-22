<?php
    return array(
        /* 自动载入的类的目录 */
        'AUTOLOAD_CLASS_DIRS' => array(
            'Lib',
            'Logic'
        ),
        /* 日志设置 */
        'LOG_RECORD'            => false,   // 默认不记录日志
        'LOG_TYPE'              => 3, // 日志记录类型 0 系统 1 邮件 3 文件 4 SAPI 默认为文件方式
        'LOG_DEST'              => '', // 日志记录目标
        'LOG_EXTRA'             => '', // 日志记录额外信息
        'LOG_LEVEL'             => 'EMERG,ALERT,CRIT,ERR',// 允许记录的日志级别
        'LOG_FILE_SIZE'         => 2097152,	// 日志文件大小限制
        'LOG_EXCEPTION_RECORD'  => false,    // 是否记录异常信息日志
        /* 数据库设置 */
        'DB_CHARSET' =>'utf8',
        /* 数据库置0 */
        'DB_CONFIG0' => array(
            'db_user'  => 'root',
            'db_pwd'   => 'root',
            'db_host'  => '127.0.0.1',
            'db_port'  => '3306',
            'db_name'  => 'test',
            'db_type'  => 'mysql',
            'connect_type' => 'mysqli',
            'table_prefix' => 't_',
        ),
        /* 数据库置1 */
        'DB_CONFIG1' => array(
            'db_user'  => 'root',
            'db_pwd'   => 'root',
            'db_host'  => '127.0.0.1',
            'db_port'  => '3306',
            'db_name'  => 'test',
            'db_type'  => 'mysql',
            'connect_type' => 'pdo',
            'table_prefix' => 't_',
        ),
        /* 邮件发送者的配置 */
        'MAIL_CONFIG' => array(
            'host' => 'smtp.163.com',
            'username' => '',
            'password' => '',
            'fromname' => '',
        ),
        /* 邮件默认接收者的配置 */
        'MAIL_SEND_TO' => '',
    );
?>
