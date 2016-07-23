<?php
namespace  MyMVC;
/**
 *自动创建文件 
 */
class Build
{
    /**
     *创建文件 
     */
    public function autoBuildFile()
    {
        !is_dir(APP_PATH) ? mkdir(APP_PATH , 0755 , true) : false;
        if (is_writeable(APP_PATH)){
           $file = array(
               APP_PATH.getConfig("DEFAULT_MODULE")."/",
               APP_PATH.getConfig("DEFAULT_MODULE")."/View/",
               APP_PATH.getConfig("DEFAULT_MODULE")."/Controller/",
               APP_PATH.getConfig("DEFAULT_MODULE")."/Common/",
               APP_PATH.getConfig("DEFAULT_MODULE")."/Config/",
               APP_PATH.getConfig("DEFAULT_MODULE")."/Model/",
               APP_COMMON_PATH,
               APP_COMMON_PATH."Common/",
               APP_CONFIG_PATH,
               RUNTIME_PATH,
               APP_LOGS_PATH,
               APP_CACHE_PATH,
               APP_DATA_PATH,
           );
           foreach ($file as $key => $value){
               if (!is_dir($value)){
                   mkdir($value , 0755 , true);
               }
           }
           //写入目录安全文件
           self::writeFile($file);
           //写入调试
          self::writeDebug(APP_PATH.getConfig("DEFAULT_MODULE")."/Controller/IndexController".EndPrefex);
           //写入配置文件
          !is_file(APP_CONFIG_PATH."config.php") ? file_put_contents(APP_CONFIG_PATH."config.php", "<?php\nreturn array(\n\t//配置项=> 配置值\n);") : false;
       }else {
           header("Content-Text=text/html;charset=UTF-8");
           die("[".APP_PATH."]目录不可写,请手动生成");
       }
    }
    /**
     *写入目录安全文件 
     */
    protected  function writeFile($file = array())
    {
        static $path ;
        if (!empty($path)){
            return ;
        }
        //默认写入权限
        defined("DEFAULT_WRITE_FILE") or define("DEFAULT_WRITE_FILE", true);
        if (DEFAULT_WRITE_FILE){
            //默认写入文件名
            defined("DEFAULT_FILE_NAME") or define("DEFAULT_FILE_NAME", "index.html");
            defined("DEFAULT_CONTENT") or define("DEFAULT_CONTENT", " ");
            $content = DEFAULT_CONTENT;
            $fileName = explode(",",DEFAULT_FILE_NAME);
            foreach ($fileName as $filePath){
                foreach ($file as $key => $value){
                   file_put_contents($value.$filePath, $content);
                   $path[$key] = $value.$filePath;
                }
            }
           
        }
    }
    /**
     *写入Action调试代码 
     */
    protected  function writeDebug($file)
    {
        !is_file($file)&& is_file(TPL_PATH."default_index.tpl") ? file_put_contents($file, file_get_contents(TPL_PATH."default_index.tpl")) : false;
    }
}