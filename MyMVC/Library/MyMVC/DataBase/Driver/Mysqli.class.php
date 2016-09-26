<?php
namespace MyMVC\DataBase\Driver;

use MyMVC\DataBase;
defined('MyMVC_PATH') or die('No resoure.');
/**
 * MySQLI 驱动类 
 */
class MySqli extends DataBase
{
    public function __construct($config) 
    {
        if (!extension_loaded('mysqli')) {
            getError(getLanage('_NOT_SUPPORT_DB_').':'.'MySqli');
        } else {
            $this->config = $config;
            if(empty($this->config['params'])) {
                $this->config['params'] =   '';
            }
        }
    }
    /**
     * 初始化连接 
     */
    public function connect($config = '', $linkNum = 0)
    {
        if (!isset(self::$linkAll[$linkNum])) {
            if (empty($config)) 
                $config = $this->config;
                self::$linkAll[$linkNum] = new \mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], is_numeric($config['hostport']) ? intval($config['hostport']) : 3306);
            if (mysqli_connect_error()) {
                getError(mysqli_connect_error());
            }
            //判断mysql 版本
            if (self::$linkAll[$linkNum]->server_version > '5.0.1')
                self::$linkAll[$linkNum]->query("set sql_mode=''");
            //设置编码
            self::$linkAll[$linkNum]->query('set names "'.$config['charset'].'"');
            
            //注销信息
            unset($this->config);
        }
        return self::$linkAll[$linkNum];
    }
    /**
     * 执行sql 语句 
     */
    public function execute($sql, $bind = array())
    {
        $this->initConnect(true);
        if (empty($this->link)) return false;
        //释放前一次查询的结果集
        $this->sql = $sql;
        if (!empty($this->queryID)) //释放结果集
        {
            $this->free();
        }
        getMemory('executeStart');
        $result = $this->link->query($sql);
        $this->debug();
        if ($result === false) {
            //保存错误
            return false;
        } else {
            $this->numRows = $this->link->affected_rows;
            $this->lastInsertId = $this->link->insert_id;
        }
        return $this->numRows;
    }
    /**
     * 释放结果集 
     */
    protected function free()
    {
        if (is_object($this->queryID)) {
            $this->queryID->free_result();            
        }
        $this->queryID = null;
    }
    /**
     * 数据库调试 
     */
    protected function debug()
    {
        if (getConfig('DB_SQL_LOG'))
        {
            getMemory('executeEnd');
            getTrace($this->sql.'[RunTime:'.getMemory('executeStart', 'executeEnd', 6).'s]','SQL');
        }
    }
}