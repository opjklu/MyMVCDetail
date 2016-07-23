<?php
namespace MyMVC;
/**
 *系统监听类 
 */
class Hook
{
    /**
     * 插件数组
     */
    private static $tags = array();
    
    /**
     *添加插件 
     */
    public static function addTag($tags)
    {
        if (!is_array($tags)){
            return   empty(self::$tags[$tags]) ? self::$tags[$tags] = $tags : false;
        }else {
            foreach ($tags as $key => $value){
                if (empty(self::$tags[$key])){
                    self::$tags[$key] = $value;
                }
            }
            return self::$tags;
        }
    }
    /**
     *动态添加插件 
     *@param string||array $tags 插件数据组或字符串
     *@param bool $isRwrite  是否覆盖插件
     *@return void
     */
    public static function import($tags , $isRwrite = false)
    {
        if ($isRwrite === true){
            self::$tags = array_merge(self::$tags , $tags);
        }elseif (is_array($tags)){
            foreach ($tags as $key => $value){
                empty(self::$tags[$key]) ? self::$tags[$key] =array() : false;
                self::$tags[$key] = array_merge(self::$tags[$key] , $value);
            }
        }
    }
    /**
     *获取某个插件 
     *@param 插件数组或字符串 $tag 
     */
    public static function getTag( $tag='')
    {
        if ($tag === '')return self::$tags;
        if (  is_string($tag)){
            return !empty(self::$tags[$tag]) ? self::$tags[$tag] : "没有此插件";
        }else{
            $tagArray = array();
            foreach ($tag as $key ){
                !empty(self::$tags[$key]) ? $tagArray[$key]= self::$tags[$key] : null;
            }
            return $tagArray;
        }
    }
   /**
    *监听某个插件的运行 
    */
    public static function listenTag($tag , &$param = NULL) 
    {
       if (!empty(self::$tags[$tag])){
           //是否开启调试模式
           if (APP_DEBUG){
               getMemory($tag."Start");//获取内存及运行时间
               getTrace("[{$tag}]--start--" , '' , 'INFO');//获取调试信息
           }
           foreach (self::$tags[$tag] as $name){
               APP_DEBUG&&getMemory($name."_start");
               //执行插件
               $result = self::exec($name ,$tag ,  $param);
               if (APP_DEBUG){
                   getMemory($name."_end");
                   getTrace("Run：[{$name}]"."[RunTime：".getMemory($name."_start" , $name."_end" , 6)."s]" , '' ,"INFO");
               }
               if ($result=="") return ;
           }
           APP_DEBUG ? getTrace("[{$tag}]--END [RunTime: ".getMemory($name."_start" , $name."_end" , 6)."s]" , '' , "INFO") : false;
       }
    }
    /**
     *执行插件 
     */
    public static function exec($name , $tag , $param)
    {
        substr($name, 0 , 8) === "Behavior" ? $tag = "run": false;
        $obj = new $name();
        return $obj->$tag($param);
    }
}