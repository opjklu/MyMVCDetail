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
    public function connect($config, $linkNum = 0)
    {
        if (!isset(self::$linkAll[$linkNum])) {
            if (empty($config)) 
                $config = $this->config;
            try {
                self::$linkAll[$linkNum] = new \mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], is_numeric($config['hostport']) ? intval($config['hostport']) : 3306);
            } catch (\ErrorException $e) {
                getError(self::$linkAll[$linkNum]->error_list);
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
}