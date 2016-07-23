<?php
namespace MyMVC;
defined("MyMVC_PATH") or exit("No Resource");
/**
 *文件存储类 
 */
class Storage
{
    /**
     *操作句柄 
     */
    protected static $handel;
    
    /**
     *连接分布式文件系统 
     */
    public static function connect($type="File" , $arg=array())
    {
        if (empty(self::$handel)){
           $class =  'MyMVC\\Storage\\Dirver\\'.ucwords($type);
            class_exists($class) ?self::$handel = new $class($arg) : die("抱歉没有此类");
        }
    }
    /**
     *调用驱动的方法 
     */
    public static function __callstatic($method , $args) 
    {
        return  method_exists(self::$handel, $method) ? call_user_func_array(array(self::$handel , $method), $args) :getError(getLanage('_METHOD_NOT_EXIST_').':'.$method);
    }
}