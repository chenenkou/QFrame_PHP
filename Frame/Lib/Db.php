<?php
define('CLIENT_MULTI_RESULTS', 131072);
/**
 * 只支持mysql
 */
class Db implements DbInterface
{
    // 连接池
    private static $_instance   = array();
    // 是否使用永久连接
    protected $pconnect         = false;
    // 当前SQL指令
    protected $queryStr         = '';
    // 最后插入ID
    protected $lastInsID        = null;
    // 返回或者影响记录数
    protected $numRows          = 0;
    // 事务指令数
    protected $transTimes       = 0;
    // 错误信息
    protected $error            = '';
    // 当前连接ID
    protected $linkID           = null;
    // 当前查询ID
    protected $queryID          = null;
    // 是否已经连接数据库
    protected $connected        = false;
    // 数据库连接参数配置
    protected $config           = '';
    // 数据库表名
    protected $tableName        = null;
    // 数据库表前缀
    protected $tablePrefix      = null;
    /**
     * 架构函数
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config='')
    {
        if ( !extension_loaded('mysql') ) {
            die('not suppert : mysql');
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
        if( !$this->connected ) {
            $config =   $this->config;
            // 处理不带端口号的socket连接情况
            $host = $config['db_host'].($config['db_port']?":{$config['db_port']}":'');
            if($this->pconnect) {
                $this->linkID = mysql_pconnect( $host, $config['db_user'], $config['db_pwd'],CLIENT_MULTI_RESULTS);
            }else{
                $this->linkID = mysql_connect( $host, $config['db_user'], $config['db_pwd'],true,CLIENT_MULTI_RESULTS);
            }
            if ( !$this->linkID || (!empty($config['db_name']) && !mysql_select_db($config['db_name'], $this->linkID)) ) {
                throw new Exception(mysql_error());
            }
            $dbVersion = mysql_get_server_info($this->linkID);
            if ($dbVersion >= "4.1") {
                //使用UTF8存取数据库 需要mysql 4.1.0以上支持
                $db_charset = isset($config['db_charset']) ? $config['db_charset'] : C('DB_CHARSET');
                mysql_query("SET NAMES '".$db_charset."'", $this->linkID);
            }
            //设置 sql_model
            if($dbVersion >'5.0.1'){
                mysql_query("SET sql_mode=''",$this->linkID);
            }
            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
            unset($this->config);
        }
    }

    /**
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     * @access public
     * @param string $str  sql指令
     * @return integer
     * @throws Exception
     */
    public function execute($str='')
    {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        $result =   mysql_query($this->queryStr, $this->linkID) ;
        if ( false === $result) {
            throw new Exception($this->error());
        } else {
            $this->numRows = mysql_affected_rows($this->linkID);
            $this->lastInsID = mysql_insert_id($this->linkID);
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
    public function query($str='')
    {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        $this->queryID = mysql_query($this->queryStr, $this->linkID);
        if ( !$this->queryID ) {
            throw new Exception($this->error());
        } else {
            $this->numRows = mysql_num_rows($this->queryID);
            return $this->fetchAll();
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
        return array_shift($res);
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->lastInsID;
    }

    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
    }

    /**
     * 释放查询结果
     * @access public
     */
    public function free()
    {
        mysql_free_result($this->queryID);
        $this->queryID = 0;
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
        return mysql_escape_string($str);
    }

    /**
     * 启动事务
     * @access public
     */
    public function startTrans()
    {
        $this->connect();
        if ( !$this->linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            mysql_query('START TRANSACTION', $this->linkID);
        }
        $this->transTimes++;
        return true;
    }

    /**
     * 事务回滚
     * @access public
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = mysql_query('ROLLBACK', $this->linkID);
            $this->transTimes = 0;
            if(!$result){
                die($this->error());
            }
        }
        return true;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = mysql_query('COMMIT', $this->linkID);
            $this->transTimes = 0;
            if(!$result){
                die($this->error());
            }
        }
        return true;
    }

    /**
     * 关闭数据库
     * @access public
     */
    public function close()
    {
        if (!empty($this->queryID))
            mysql_free_result($this->queryID);
        if ($this->linkID && !mysql_close($this->linkID)){
            die($this->error());
        }
        $this->linkID = 0;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @access public
     * @return string
     */
    public function error()
    {
        $this->error = mysql_error($this->linkID);
        if($this->queryStr!=''){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr."\n";
        }
        return $this->error;
    }

    /**
     * 获得所有的查询数据
     * @access private
     * @return array
     */
    private function fetchAll()
    {
        if ( !$this->queryID )
            throw new Exception($this->error());
        //返回数据集
        $result = array();
        if($this->numRows >0) {
            while($row = mysql_fetch_assoc($this->queryID)){
                $result[]   =   $row;
            }
            mysql_data_seek($this->queryID,0);
        }
        return $result;
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }
}