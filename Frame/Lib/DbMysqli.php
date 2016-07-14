<?php

/**
 * Mysqli数据库驱动类
 */
class DbMysqli implements DbInterface
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
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config='')
    {
        if ( !extension_loaded('mysqli') ) {
            die('not suppert : mysqli');
        }
        // 设置表前缀
        if (!isset($this->config['table_prefix'])) $this->config['table_prefix'] = '';
        $this->tablePrefix = $this->config['table_prefix'];
    }

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
     * @throws ThinkExecption
     */
    public function connect() {
        if ( $this->connected )
            return true;

        $config = $this->config;
        try {
            $this->linkID = new mysqli(
                $config['db_host'],
                $config['db_user'],
                $config['db_pwd'],
                $config['db_name'],
                $config['db_port'] ? intval($config['db_port']) : 3306
            );
            if (mysqli_connect_errno()) throw new Exception(mysqli_connect_error());
            $dbVersion = $this->linkID->server_version;
            // 设置数据库编码
            $this->linkID->query("SET NAMES '".C('DB_CHARSET')."'");
            //设置 sql_model
            if($dbVersion >'5.0.1')
                $this->linkID->query("SET sql_mode=''");
            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
            unset($this->config);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 执行语句
     * @access public
     * @param string $str  sql指令
     * @return integer
     */
    public function execute($str)
    {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        $result =   $this->linkID->query($str);
        if ( false === $result ) {
            throw new Exception($this->error());
        } else {
            $this->numRows = $this->linkID->affected_rows;
            $this->lastInsID = $this->linkID->insert_id;
            return $this->numRows;
        }
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function query($str) {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        $this->queryID = $this->linkID->query($str);
        // 对存储过程改进
        if( $this->linkID->more_results() ){
            while (($res = $this->linkID->next_result()) != NULL) {
                $res->free_result();
            }
        }
        if ( false === $this->queryID ) {
            throw new Exception($this->error());
        } else {
            $this->numRows  = $this->queryID->num_rows;
            return $this->getAll();
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
        $this->queryID->free_result();
        $this->queryID = null;
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
     * 取得数据表的字段信息
     * @access public
     * @return array
     */
    public function getColumns($table_name)
    {
        $res =   $this->query('SHOW COLUMNS FROM '.$table_name);
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
     * @static
     * @access public
     * @param string $str  SQL指令
     * @return string
     */
    public function escapeString($str)
    {
        if ($this->linkID) {
            return  $this->linkID->real_escape_string($str);
        } else {
            return addslashes($str);
        }
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        $this->connect();
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->linkID->autocommit(false);
        }
        $this->transTimes++;
        return ;
    }

    /**
     * 事务回滚
     * @access public
     * @return bool
     * @throws Exception
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = $this->linkID->rollback();
            $this->transTimes = 0;
            if(!$result){
                throw new Exception($this->error());
            }
        }
        return true;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return bool
     * @throws Exception
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = $this->linkID->commit();
            $this->linkID->autocommit( true);
            $this->transTimes = 0;
            if(!$result){
                throw new Exception($this->error());
            }
        }
        return true;
    }

    /**
     * 关闭数据库
     * @access public
     * @return volid
     */
    public function close()
    {
        if ($this->linkID){
            $this->linkID->close();
        }
        $this->linkID = null;
    }

    /**
     * 获得所有的查询数据
     * @access private
     * @return array
     */
    private function getAll()
    {
        //返回数据集
        $result = array();
        if($this->numRows>0) {
            //返回数据集
            for($i=0;$i<$this->numRows ;$i++ ){
                $result[$i] = $this->queryID->fetch_assoc();
            }
            $this->queryID->data_seek(0);
        }
        return $result;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @static
     * @access public
     * @return string
     */
    public function error()
    {
        $this->error = $this->linkID->errno.':'.$this->linkID->error;
        if('' != $this->queryStr){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }
}