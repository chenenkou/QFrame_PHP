<?php

/**
 * 数据库操作接口
 * Date: 16/6/20
 * Time: 下午11:17
 */
interface DbInterface
{
    // 连接数据库方法
    public function connect();
    // 执行语句
    public function execute($str);
    // 返回数据集
    public function query($str);
    // 返回一条记录
    public function find($str);
    // 返回一条数目
    public function count($str);
    // 获取最近插入的ID
    public function getLastInsID();
    // 获取最近一次查询的sql语句
    public function getLastSql();
    // 释放查询结果
    public function free();
    // 获取表前缀
    public function getTablePrefix();
    // 获取一个表中的字段名
    public function getColumns($table_name);
    // 数据安全处理
    public function escapeString($str);
    // 启动事务
    public function startTrans();
    // 事务回滚
    public function rollback();
    // 用于非自动提交状态下面的查询提交
    public function commit();
    // 关闭数据库
    public function close();
    // 数据库错误信息
    public function error();
}