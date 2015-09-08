<?php
/**
 * 基础数据模型
 * Date: 15-7-28
 * Time: 下午4:24
 */

class Model {
    //数据库连接
    protected $_db;

    /**
     * 初始化
     */
    public function __construct() {
        $this->connectDb('DB_CONFIG0');
    }

    /**
     * 连接数据库
     * @param $dbConfig
     */
    public function connectDb($dbConfig) {
        $this->_db = D($dbConfig);
    }

    /**
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function query($sql) {
        return $this->_db->query($sql);
    }

    /**
     * 执行查询 主要针对 SELECT等指令
     * 返回一条字段
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function find($sql) {
        return $this->_db->find($sql);
    }

    /**
     * 执行查询 主要针对 SELECT等指令
     * 返回一条数目
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function count($sql) {
        return $this->_db->count($sql);
    }

    /**
     * 获取一个表中的字段名
     * 返回字段名数组
     * @access public
     * @param string $table_name  表名
     * @return mixed field为字段名 pri为主键
     */
    public function getColumns($table_name) {
        return $this->_db->getColumns($table_name);
    }

    /**
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     * @access public
     * @param string $str  sql指令
     * @return integer
     */
    public function execute($sql) {
        $result = $this->_db->execute($sql);
        if (!(stripos($sql, 'INSERT') === false)) {
            if ($result > 0) {
                $result = $this->getLastInsID();
            }
        }
        return $result;
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans() {
        return $this->_db->startTrans();
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolen
     */
    public function commit() {
        return $this->_db->commit();
    }

    /**
     * 事务回滚
     * @access public
     * @return boolen
     */
    public function rollback() {
        return $this->_db->rollback();
    }

    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql() {
        return $this->_db->getLastSql();
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID(){
        return $this->_db->getLastInsID();
    }

    /**
     * 保存数据
     * @param $data 需要保存的数据
     * @param string $table 表名
     * @return int
     */
    public function insert($data, $table='') {
        // 判断是单条数据还是多条数据
        if (is_array(end($data))) {
            list($fields, $values) = doubleArr2InsertSql($data);
        } else {
            list($fields, $values) = arr2InsertSql($data);
            $values = "({$values})";
        }
        // 处理需要插入的表名
        if (empty($table))
            $table = $this->_db->getTablePrefix() . strtolower(get_class($this));

        // 拼接sql需要插入数据库
        $sql = "INSERT INTO {$table} ({$fields}) VALUES {$values}";
        return $this->execute($sql);
    }
}