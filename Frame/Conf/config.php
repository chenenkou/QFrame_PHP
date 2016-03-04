<?php
	return array(
        /* 自动载入的类的目录 */
        'AUTOLOAD_CLASS_DIRS' => array(
            'Lib',
            'Component'
        ),
        'DB_CHARSET' =>'utf8',
		/* 数据库置0 */
		'DB_CONFIG0' => array(
			'db_user'  => 'root',
			'db_pwd'   => 'root',
			'db_host'  => '127.0.0.1',
			'db_port'  => '3306',
			'db_name'  => 'test',
            'db_type'  => 'mysql',
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
