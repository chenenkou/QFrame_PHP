<?php

/**
 * PDO数据库操作类
 */
class DbPdo implements DbInterface
{
    private static $_instance   = array();
    // 是否已经连接数据库
    protected $connected        = false;
    public $linkID= null; //数据库连接
    private $PDOStatement = null; //预准备
    public $affectedRows; //受影响条数
    protected $tablePrefix;
    protected $queryStr;
    protected $error = '';

    /**
     * 架构函数
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config=''){
        if ( !class_exists('PDO') ) {
            die('not suppert : PDO');
        }
        $this->config   =  $config;
        // 设置表前缀
        if (!isset($this->config['table_prefix'])) $this->config['table_prefix'] = '';
        $this->tablePrefix = $this->config['table_prefix'];
    }

    /**
     * 取得数据库类实例
     * @static
     * @access public
     * @return mixed 返回数据库驱动类
     */
    public function getInstance($k=0, $db_config='')
    {
        if (!isset(self::$_instance[$k])){
            self::$_instance[$k] = new self($db_config);
        }
        return self::$_instance[$k];
    }

    public function connect()
    {
        if ( !$this->connected ) {
            $config = $this->config;
            $dsn = "mysql:host=" . $config['db_host'] . ';dbname=' . $config['db_name'];
            $username = $config['db_user'];
            $password = $config['db_pwd'];
            try {
                $this->linkID = new Pdo($dsn, $username, $password);
                $character = isset($config['db_charset']) ? $config['db_charset'] : C("DB_CHARSET");
                $sql = "SET character_set_connection=$character,character_set_results=$character,character_set_client=binary";
                $this->linkID->query($sql);
            } catch (PDOException $e) {
                return false;
            }

            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
            unset($this->config);
        }

        return true;
    }

    //获得最后插入的ID号
    public function getLastInsID()
    {
        return $this->linkID->lastInsertId();
    }

    //获得受影响的行数
    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    //数据安全处理
    public function escapeString($str)
    {
        return addslashes($str);
    }

    //执行SQL没有返回值
    public function execute($sql)
    {
        $this->connect();
        /**
         * 记录SQL语句
         */
        if ( $sql != '' ) $this->queryStr = $sql;
        //释放结果
        if (!$this->PDOStatement)
            $this->free();
        $this->PDOStatement = $this->linkID->prepare($sql);
        //预准备失败
        if ($this->PDOStatement === false) {
            $this->error();
            return false;
        }
        $result = $this->PDOStatement->execute();
        //执行SQL失败
        if ($result === false) {
            $this->error();
            return false;
        } else {
            $insert_id = $this->linkID->lastInsertId();
            return $insert_id ? $insert_id : TRUE;
        }
    }

    //发送查询 返回数组
    public function query($sql)
    {
        //发送SQL
        if (!$this->execute($sql)) {
            return false;
        }
        $list = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        //受影响条数
        $this->affectedRows = count($list);

        return empty($list) ? array() : $list;
    }

    //遍历结果集(根据INSERT_ID)
    protected function fetch()
    {
        $res = $this->lastquery->fetch(PDO::FETCH_ASSOC);
        if (!$res) {
            $this->free();
        }
        return $res;
    }

    // 返回一条记录
    public function find($str)
    {

    }
    // 返回一条数目
    public function count($str)
    {

    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @access public
     * @return string
     */
    public function error() {
        $this->error = $this->linkID->errorCode();
        if($this->queryStr!=''){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr."\n";
        }
        return $this->error;
    }

    //释放结果集
    public function free()
    {
        $this->PDOStatement = NULL;
    }


    // 获得MYSQL版本信息
    public function getVersion()
    {
        return $this->linkID->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    //开启事务处理
    public function startTrans()
    {
        $this->linkID->beginTransaction();
    }

    //提供一个事务
    public function commit()
    {
        $this->linkID->commit();
    }

    //回滚事务
    public function rollback()
    {
        $this->linkID->rollback();
    }

    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql() {
        return $this->queryStr;
    }

    // 释放连接资源
    public function close()
    {
        $this->PDOStatement = NULL;
        if (is_object($this->linkID)) {
            $this->linkID= NULL;
        }
    }

    //析构函数  释放连接资源
    public function __destruct()
    {
        $this->close();
    }

    // 获取表前缀
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    // 获取一个表中的字段名
    public function getColumns($table_name)
    {

    }
}