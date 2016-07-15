<?php

/**
 * PDO数据库操作类
 */
class DbPdo implements DbInterface
{
    // 连接池
    private static $_instance   = array();
    // 是否使用永久连接
    protected $pconnect         = false;
    // 是否已经连接数据库
    protected $connected        = false;
    // 当前SQL指令
    protected $queryStr         = '';
    // 最后插入ID
    protected $lastInsID        = null;
    // 返回或者影响记录数
    protected $numRows          = 0;
    // 错误信息
    protected $error            = '';
    // 当前连接ID
    protected $linkID           = null;
    // 预准备当前查询ID
    private $PDOStatement       = null;
    // 数据库连接参数配置
    protected $config           = '';
    // 数据库表名
    protected $tableName        = null;
    // 数据库表前缀
    protected $tablePrefix      = null;

    /**
     * 构造函数
     * DbPdo constructor.
     * @param string $config
     */
    public function __construct($config='')
    {
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

    /**
     * 取得数据库类实例
     * @static
     * @access public
     * @param int $k 标示ID
     * @param string $db_config 数据库配置
     * @return mixed 返回数据库驱动类
     */
    public static function getInstance($k=0, $db_config='')
    {
        if (!isset(self::$_instance[$k])){
            self::$_instance[$k] = new self($db_config);
        }
        return self::$_instance[$k];
    }

    /**
     * 连接数据库方法
     * @access public
     */
    public function connect()
    {
        if ( !$this->connected ) {
            $config = $this->config;
            $dsn = "mysql:host=" . $config['db_host'] . ';dbname=' . $config['db_name'] . ';port=' . $config['db_port'];
            $username = $config['db_user'];
            $password = $config['db_pwd'];
            $params = array();
            if($this->pconnect){
                //开启长连接，添加到配置数组中
                $params[constant("PDO::ATTR_PERSISTENT")]=true;
            }
            try {
                $this->linkID = new Pdo($dsn, $username, $password, $params);
                $character = isset($config['db_charset']) ? $config['db_charset'] : C("DB_CHARSET");
                $this->linkID->exec('SET NAMES '.$character);
            } catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }

            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
            unset($this->config);
        }

        return true;
    }

    /**
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     * @access public
     * @param string $str  sql指令
     * @return integer
     * @throws Exception
     */
    public function execute($str)
    {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->PDOStatement ) {    $this->free();    }
        $this->PDOStatement = $this->linkID->prepare($str);
        if(false === $this->PDOStatement)
            throw new Exception($this->error());
        $result = $this->PDOStatement->execute();
        if($result === false){
            throw new Exception($this->error());
        }else{
            $this->numRows = $this->PDOStatement->rowCount();
            $this->lastInsertId=$this->linkID->lastInsertId();
            return $this->numRows;
        }
    }

    /**
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function query($str)
    {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->PDOStatement ) {    $this->free();    }
        $this->PDOStatement = $this->linkID->prepare($str);
        if(false === $this->PDOStatement)
            throw new Exception($this->error());
        $result = $this->PDOStatement->execute();
        //执行SQL失败
        if ($result === false) {
            throw new Exception($this->error());
        } else {
            $this->numRows = $this->PDOStatement->rowCount();
            $list = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
            return empty($list) ? array() : $list;
        }
    }

    /**
     * 执行查询 主要针对 SELECT等指令
     * 返回一条记录
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function find($str)
    {
        $res = $this->query($str);
        if (empty($res))
            return array();
        return array_shift($res);
    }

    /**
     * 执行查询 主要针对 SELECT等指令
     * 返回一条数目
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function count($str)
    {
        $res = $this->find($str);
        if (empty($res))
            return 0;
        return array_shift($res);
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->linkID->lastInsertId();
    }

    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql() {
        return $this->queryStr;
    }

    /**
     * 释放查询结果
     * @access public
     */
    public function free()
    {
        $this->PDOStatement = NULL;
    }

    /**
     * 获取表前缀
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * 获取一个表中的字段名
     * 返回字段名数组
     * @access public
     * @param string $table_name  表名
     * @return mixed field为字段名 pri为主键
     */
    public function getColumns($table_name)
    {
        $str = 'SHOW COLUMNS FROM '.$table_name;
        $res = $this->query($str);
        $arr = array();
        foreach($res as $k=>$v) {
            $arr['field'][] = $v['Field'];
            if($v['Key'] == 'PRI') {
                $arr['pri'] = $v['Field'];
            }
        }
        return $arr;
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str  SQL字符串
     * @return string
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @access public
     * @return string
     */
    public function error()
    {
        if($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $this->error = $error[1].':'.$error[2];
        }else{
            $this->error = '';
        }
        if('' != $this->queryStr){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     * 启动事务
     * @access public
     */
    public function startTrans()
    {
        $this->connect();
        if ( !$this->linkID ) return false;
        $this->linkID->beginTransaction();
    }

    /**
     * 事务回滚
     * @access public
     */
    public function commit()
    {
        $this->linkID->commit();
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     */
    public function rollback()
    {
        $this->linkID->rollback();
    }

    /**
     * 关闭数据库
     * @access public
     */
    public function close()
    {
        $this->PDOStatement = NULL;
        if (is_object($this->linkID)) {
            $this->linkID= NULL;
        }
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {
        $this->close();
    }
}