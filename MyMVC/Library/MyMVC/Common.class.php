<?php
namespace  MyMVC;

/**
 *MyMVC 公共操作类
 *@author 王强 
 */
class Common
{
    
    private static $methods = array();
    
    private   $class = null;
    
    /**
     * 对外关闭构造函数 
     */
    private function __construct($class_name, $methods = null)
    {
        if (!class_exists($class_name))
        {
            getError(getLanage('_CLASS_NOT_EXIST_').':'.$class_name);
        }
        else 
        {
            $this->class =  new $class_name();
            self::$methods[$methods] = !empty($methods) ? $methods : get_class_methods($class_name);
        }
    }
    
    /**
     * 实例化本类 
     */
    public static  function getInitation($class_name, $methods = null)
    {
        static $init = null;
        if (!is_object($init))
        {
            $init = new self($methods);
            return $init;
        }
        else 
        {
            return $init;
        }
    }
    
    public function __set($name , $value)
    {
        if (empty(self::$methods[$name]))
            self::$name = $value;
    }
    
    public function __get($name)
    {
        return !empty(self::$methods[$name]) ? self::$methods[$name] : null;
    }
    
    public function __callstatic($methods, $args)
    {
        $this->hasMethods($methods);
        return   call_user_func_array(array($this->class , $methods), $args) ;
    }
    
    public function __call($methods, $args)
    {
        $this->hasMethods($methods);
        return  call_user_func_array(array($this->class , $methods), $args) ;
    }
    
    public function __destruct(Common $obj)
    {
        if ($obj instanceof Common)
            unset($obj);
    }
    
    public function hasMethods($methods)
    {
        return array_key_exists($methods, self::$methods) ? true : getError(getLanage('_METHOD_NOT_EXIST_').':'.$methods);
    }
}