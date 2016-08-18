<?php

/**
 * 基础数据模型
 * Date: 15-7-28
 * Time: 下午4:24
 */
class Model
{
    // 数据库连接
    protected $_db;
    // 数据库配置 - 子类可以修改该属性切换数据库
    protected $db_config = 'DB_CONFIG0';
    // 模型对应表名 - 子类可以修改该属性标记正确的表名
    protected $table_name = null;

    /**
     * 初始化
     */
    public function __construct()
    {
        $this->connectDb();
    }

    /**
     * 连接数据库
     */
    public function connectDb()
    {
        $this->_db = $this->getDbConnection();
        $this->table_name = $this->tableName();
    }

    /**
     * 获取数据库连接配置
     * 子类可以覆盖该方法切换数据库
     * @return Model
     */
    public function getDbConnection()
    {
        return D($this->db_config);
    }

    /**
     * 获取模型对应表名
     * 子类应该覆盖该方法表明对应数据表
     * @return null
     */
    public function tableName()
    {
        return $this->table_name ? $this->table_name : $this->_db->getTablePrefix() . hump2underline(get_class($this));
    }

    /**
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     * @access public
     * @param string $str sql指令
     * @return mixed
     */
    public function query($sql)
    {
        return $this->_db->query($sql);
    }

    /**
     * 执行查询 主要针对 SELECT等指令
     * 返回一条字段
     * @access public
     * @param string $sql sql指令
     * @return mixed
     */
    public function find($sql)
    {
        return $this->_db->find($sql);
    }

    /**
     * 执行查询 主要针对 SELECT等指令
     * 返回一条数目
     * @access public
     * @param string $sql sql指令
     * @return mixed
     */
    public function count($sql)
    {
        return $this->_db->count($sql);
    }

    /**
     * 获取一个表中的字段名
     * 返回字段名数组
     * @access public
     * @param string $table_name 表名
     * @return mixed field为字段名 pri为主键
     */
    public function getColumns($table_name)
    {
        return $this->_db->getColumns($table_name);
    }

    /**
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     * @access public
     * @param string $sql sql指令
     * @return integer
     */
    public function execute($sql)
    {
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
    public function startTrans()
    {
        return $this->_db->startTrans();
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return bool
     */
    public function commit()
    {
        return $this->_db->commit();
    }

    /**
     * 事务回滚
     * @access public
     * @return bool
     */
    public function rollback()
    {
        return $this->_db->rollback();
    }

    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->_db->getLastSql();
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->_db->getLastInsID();
    }

    /**
     * 新增数据前的处理
     * @param array $data 需要处理的数据
     * @param string $table 表名
     * @return array
     */
    private function _addData($data, $table = '')
    {
        // 判断是单条数据还是多条数据
        if (is_array(end($data))) {
            list($fields, $values) = doubleArr2InsertSql($data);
        } else {
            list($fields, $values) = arr2InsertSql($data);
            $values = "({$values})";
        }
        // 处理需要插入的表名
        if (empty($table))
            $table = $this->tableName();

        return array($table, $fields, $values);
    }

    /**
     * 保存数据
     * @param array $data 需要保存的数据
     * @param string $table 表名
     * @return int
     */
    public function insert($data, $table = '')
    {
        list($table, $fields, $values) = $this->_addData($data, $table);

        $sql = "INSERT INTO {$table} ({$fields}) VALUES {$values}";
        return $this->execute($sql);
    }

    /**
     * 替换数据
     * @param array $data 需要保存的数据
     * @param string $table 表名
     * @return int
     */
    public function replace($data, $table = '')
    {
        list($table, $fields, $values) = $this->_addData($data, $table);

        $sql = "REPLACE INTO {$table} ({$fields}) VALUES {$values}";
        return $this->execute($sql);
    }
}
