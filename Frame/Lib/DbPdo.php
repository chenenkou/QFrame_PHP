<?php

/**
 * PDO数据库驱动
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Db
 * @author    liu21st <liu21st@gmail.com>
 */
class DbPdo {

    protected $PDOStatement = null;
    private   $table        = '';
    static private $_instance   = array();
    // 是否使用永久连接
    protected $pconnect         = false;
    // 当前SQL指令
    protected $queryStr         = '';
    // 最后插入ID
    protected $lastInsID        = null;
    // 返回或者影响记录数
    protected $numRows          = 0;
    // 返回字段数
    protected $numCols          = 0;
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
    // 数据库类型
    protected $dbType           = null;

    /**
     * 架构函数 读取数据库配置信息
     * @access public
     * @param array $config 数据库配置数组
     */
    public function __construct($config=''){
        if ( !class_exists('PDO') ) {
            die('not suppert : PDO');
        }
        if(!empty($config)) {
            if ( !isset($config['dsn']) ) {
                $config['db_type'] = isset($config['db_type']) ? $config['db_type'] : 'mysql';
                $config['dsn'] = $config['db_type'] . ':host=' . $config['db_host'] . ';dbname=' . $config['db_name'];
            }
            $this->config   =   $config;
            if(empty($this->config['params'])) {
                $this->config['params'] =   array();
            }
        }
    }

    /**
     * 根据DSN获取数据库类型 返回大写
     * @access protected
     * @param string $dsn  dsn字符串
     * @return string
     */
    protected function _getDsnType($dsn) {
        $match  =  explode(':',$dsn);
        $dbType = strtoupper(trim($match[0]));
        return $dbType;
    }

    /**
     * 连接数据库方法
     * @access public
     */
    public function connect() {
        if( !$this->connected ) {
            $config =   $this->config;
            if($this->pconnect) {
                $config['params'][PDO::ATTR_PERSISTENT] = true;
            }
            //$config['params'][PDO::ATTR_CASE] = C("DB_CASE_LOWER")?PDO::CASE_LOWER:PDO::CASE_UPPER;
            try{
                $this->linkID = new PDO( $config['dsn'], $config['db_user'], $config['db_pwd'],$config['params']);
            }catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }
            // 因为PDO的连接切换可能导致数据库类型不同，因此重新获取下当前的数据库类型
            $this->dbType = $this->_getDsnType($config['dsn']);
            if(in_array($this->dbType,array('MSSQL','ORACLE','IBASE','OCI'))) {
                // 由于PDO对于以上的数据库支持不够完美，所以屏蔽了 如果仍然希望使用PDO 可以注释下面一行代码
                throw new Exception('由于目前PDO暂时不能完美支持'.$this->dbType.' 请使用官方的'.$this->dbType.'驱动');
            }
            $this->linkID->exec('SET NAMES '.C('DB_CHARSET'));
            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
            unset($this->config);
        }
    }

    /**
     * 释放查询结果
     * @access public
     */
    public function free() {
        $this->PDOStatement = null;
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param string $str  sql指令
     * @param array $bind 参数绑定
     * @return mixed
     */
    public function query($str,$bind=array()) {
        $this->connect();
        if ( !$this->linkID ) return false;
        $this->queryStr = $str;
        if(!empty($bind)){
            $this->queryStr     .=   '[ '.print_r($bind,true).' ]';
        }
        //释放前次的查询结果
        if ( !empty($this->PDOStatement) ) $this->free();

        $this->PDOStatement = $this->linkID->prepare($str);
        if(false === $this->PDOStatement)
            throw new Exception($this->error());
        $result =   $this->PDOStatement->execute($bind);
        if ( false === $result ) {
            $this->error();
            return false;
        } else {
            return $this->getAll();
        }
    }

    /**
     * 执行语句
     * @access public
     * @param string $str  sql指令
     * @param array $bind 参数绑定
     * @return integer
     */
    public function execute($str,$bind=array()) {
        $this->connect();
        if ( !$this->linkID ) return false;
        $this->queryStr = $str;
        if(!empty($bind)){
            $this->queryStr     .=   '[ '.print_r($bind,true).' ]';
        }
        $flag = false;
        if($this->dbType == 'OCI')
        {
            if(preg_match("/^\s*(INSERT\s+INTO)\s+(\w+)\s+/i", $this->queryStr, $match)) {
                $this->table = C("DB_SEQUENCE_PREFIX").str_ireplace(C("DB_PREFIX"), "", $match[2]);
                $flag = (boolean)$this->query("SELECT * FROM user_sequences WHERE sequence_name='" . strtoupper($this->table) . "'");
            }
        }//modify by wyfeng at 2009.08.28
        //释放前次的查询结果
        if ( !empty($this->PDOStatement) ) $this->free();

        $this->PDOStatement	=	$this->_linkID->prepare($str);
        if(false === $this->PDOStatement) {
            throw new Exception($this->error());
        }
        $result	=	$this->PDOStatement->execute($bind);

        if ( false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->PDOStatement->rowCount();
            if($flag || preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = $this->getLastInsertId();
            }
            return $this->numRows;
        }
    }

    /**
     * 启动事务
     * @access public
     * @return boolen
     */
    public function startTrans() {
        $this->connect();
        if ( !$this->linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->linkID->beginTransaction();
        }
        $this->transTimes++;
        return true;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolen
     */
    public function commit() {
        if ($this->transTimes > 0) {
            $result = $this->linkID->commit();
            $this->transTimes = 0;
            if(!$result){
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * 事务回滚
     * @access public
     * @return boolen
     */
    public function rollback() {
        if ($this->transTimes > 0) {
            $result = $this->linkID->rollback();
            $this->transTimes = 0;
            if(!$result){
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     * @access private
     * @return array
     */
    private function getAll() {
        //返回数据集
        $result =   $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        $this->numRows = count( $result );
        return $result;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     */
    public function getFields($tableName) {
        $this->connect();
        if(C('DB_DESCRIBE_TABLE_SQL')) {
            // 定义特殊的字段查询SQL
            $sql   = str_replace('%table%',$tableName,C('DB_DESCRIBE_TABLE_SQL'));
        }else{
            switch($this->dbType) {
                case 'MSSQL':
                case 'SQLSRV':
                    $sql   = "SELECT   column_name as 'Name',   data_type as 'Type',   column_default as 'Default',   is_nullable as 'Null'
        FROM    information_schema.tables AS t
        JOIN    information_schema.columns AS c
        ON  t.table_catalog = c.table_catalog
        AND t.table_schema  = c.table_schema
        AND t.table_name    = c.table_name
        WHERE   t.table_name = '$tableName'";
                    break;
                case 'SQLITE':
                    $sql   = 'PRAGMA table_info ('.$tableName.') ';
                    break;
                case 'ORACLE':
                case 'OCI':
                    $sql   = "SELECT a.column_name \"Name\",data_type \"Type\",decode(nullable,'Y',0,1) notnull,data_default \"Default\",decode(a.column_name,b.column_name,1,0) \"pk\" "
                        ."FROM user_tab_columns a,(SELECT column_name FROM user_constraints c,user_cons_columns col "
                        ."WHERE c.constraint_name=col.constraint_name AND c.constraint_type='P' and c.table_name='".strtoupper($tableName)
                        ."') b where table_name='".strtoupper($tableName)."' and a.column_name=b.column_name(+)";
                    break;
                case 'PGSQL':
                    $sql   = 'select fields_name as "Name",fields_type as "Type",fields_not_null as "Null",fields_key_name as "Key",fields_default as "Default",fields_default as "Extra" from table_msg('.$tableName.');';
                    break;
                case 'IBASE':
                    break;
                case 'MYSQL':
                default:
                    $sql   = 'DESCRIBE '.$tableName;//备注: 驱动类不只针对mysql，不能加``
            }
        }
        $result = $this->query($sql);
        $info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
                $val            =   array_change_key_case($val);
                $val['name']    =   isset($val['name'])?$val['name']:"";
                $val['type']    =   isset($val['type'])?$val['type']:"";
                $name           =   isset($val['field'])?$val['field']:$val['name'];
                $info[$name]    =   array(
                    'name'    => $name ,
                    'type'    => $val['type'],
                    'notnull' => (bool)(((isset($val['null'])) && ($val['null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))), // not null is empty, null is yes
                    'default' => isset($val['default'])? $val['default'] :(isset($val['dflt_value'])?$val['dflt_value']:""),
                    'primary' => isset($val['key'])?strtolower($val['key']) == 'pri':(isset($val['pk'])?$val['pk']:false),
                    'autoinc' => isset($val['extra'])?strtolower($val['extra']) == 'auto_increment':(isset($val['key'])?$val['key']:false),
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     * @access public
     */
    public function getTables($dbName='') {
        if(C('DB_FETCH_TABLES_SQL')) {
            // 定义特殊的表查询SQL
            $sql   = str_replace('%db%',$dbName,C('DB_FETCH_TABLES_SQL'));
        }else{
            switch($this->dbType) {
                case 'ORACLE':
                case 'OCI':
                    $sql   = 'SELECT table_name FROM user_tables';
                    break;
                case 'MSSQL':
                case 'SQLSRV':
                    $sql   = "SELECT TABLE_NAME	FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                    break;
                case 'PGSQL':
                    $sql   = "select tablename as Tables_in_test from pg_tables where  schemaname ='public'";
                    break;
                case 'IBASE':
                    // 暂时不支持
                    throw_exception(L('_NOT_SUPPORT_DB_').':IBASE');
                    break;
                case 'SQLITE':
                    $sql   = "SELECT name FROM sqlite_master WHERE type='table' "
                        . "UNION ALL SELECT name FROM sqlite_temp_master "
                        . "WHERE type='table' ORDER BY name";
                    break;
                case 'MYSQL':
                default:
                    if(!empty($dbName)) {
                        $sql    = 'SHOW TABLES FROM '.$dbName;
                    }else{
                        $sql    = 'SHOW TABLES ';
                    }
            }
        }
        $result = $this->query($sql);
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * limit分析
     * @access protected
     * @param mixed $lmit
     * @return string
     */
    protected function parseLimit($limit) {
        $limitStr    = '';
        if(!empty($limit)) {
            switch($this->dbType){
                case 'PGSQL':
                case 'SQLITE':
                    $limit  =   explode(',',$limit);
                    if(count($limit)>1) {
                        $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
                    }else{
                        $limitStr .= ' LIMIT '.$limit[0].' ';
                    }
                    break;
                case 'MSSQL':
                case 'SQLSRV':
                    break;
                case 'IBASE':
                    // 暂时不支持
                    break;
                case 'ORACLE':
                case 'OCI':
                    break;
                case 'MYSQL':
                default:
                    $limitStr .= ' LIMIT '.$limit.' ';
            }
        }
        return $limitStr;
    }

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key) {
        if($this->dbType=='MYSQL'){
            $key   =  trim($key);
            if(!preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
                $key = '`'.$key.'`';
            }
        }
        return $key;
    }

    /**
     * 关闭数据库
     * @access public
     */
    public function close() {
        $this->linkID = null;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @access public
     * @return string
     */
    public function error() {
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
     * SQL指令安全过滤
     * @access public
     * @param string $str  SQL指令
     * @return string
     */
    public function escapeString($str) {
        switch($this->dbType) {
            case 'PGSQL':
            case 'MSSQL':
            case 'SQLSRV':
            case 'MYSQL':
                return addslashes($str);
            case 'IBASE':
            case 'SQLITE':
            case 'ORACLE':
            case 'OCI':
                return str_ireplace("'", "''", $str);
        }
    }

    /**
     * value分析
     * @access protected
     * @param mixed $value
     * @return string
     */
    protected function parseValue($value) {
        if(is_string($value)) {
            $value =  strpos($value,':') === 0 ? $this->escapeString($value) : '\''.$this->escapeString($value).'\'';
        }elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
            $value =  $this->escapeString($value[1]);
        }elseif(is_array($value)) {
            $value =  array_map(array($this, 'parseValue'),$value);
        }elseif(is_bool($value)){
            $value =  $value ? '1' : '0';
        }elseif(is_null($value)){
            $value =  'null';
        }
        return $value;
    }

    /**
     * 获取最后插入id
     * @access public
     * @return integer
     */
    public function getLastInsertId() {
        switch($this->dbType) {
            case 'PGSQL':
            case 'SQLITE':
            case 'MSSQL':
            case 'SQLSRV':
            case 'IBASE':
            case 'MYSQL':
                return $this->linkID->lastInsertId();
            case 'ORACLE':
            case 'OCI':
                $sequenceName = $this->table;
                $vo = $this->query("SELECT {$sequenceName}.currval currval FROM dual");
                return $vo?$vo[0]["currval"]:0;
        }
    }
}