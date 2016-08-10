<?php
namespace MyMVC;

/**
 * 分发器类
 */
class Dispatcher
{
    private static $filePath = array();
    /**
     * 检测url
     */
    public static function checkURL()
    {
        //获取默认配置
        $model = getConfig("VAR_MODULE");
        
        //获取默认Controller
        $controller = getConfig("VAR_CONTROLLER");
        
        //获取默认操作
        $action = getConfig("VAR_ACTION");
        
        $controllerPerfex = getConfig('CONTROLLER_PEFEX');
        //获取当前访问的url得参数
        $urlParam = $_SERVER['QUERY_STRING'];
        //获取url 模式 1=> phpinfo 模式
        $urlMode = getConfig("URL_MODEL");
         if ($urlMode === 2){//路径模式
            // http:://www.wq.com/index.php/Home/Index/Index/page/1;
            
           $pathInfo = count(explode('/',$_SERVER['PHP_SELF'])) <4 ? $_SERVER['PHP_SELF'].'/Home/Index/Index' : $_SERVER['PHP_SELF'];
           $urlParamArray      =  explode('/',str_replace('.php/', '', strstr($pathInfo, ".php")));//Home/Index/index/page/1
           //获取所在文件
           $file = APP_PATH.$urlParamArray[0].'/'.getConfig("DEFAULT_C_LAYER").'/'.$urlParamArray[1].$controllerPerfex.EndPrefex;
           define("__MODULE__", $urlParamArray[0]);
           define("__CONTROLLER__", $urlParamArray[1].$controllerPerfex);
           define("__ACTION__", $urlParamArray[2]);
           is_file($file) ? : die(getError(getLanage("_MODULE_NOT_EXIST_").":".__MODULE__));
          //处理url剩余参数
           foreach ($urlParamArray as $key => &$value){
               if ($key <=2)unset($value);
               else {
                $key % 2 === 1 ?$keyValue[]=$value : ($key % 2 ===0 ? $valueKey[]  = $value : false);
               } 
           }
          if (!empty($keyValue) && !empty($valueKey)){
              foreach ($keyValue as $keys => $values){
                  $_GET[$values] = $valueKey[$keys];
              }
          }
        }
        //获取默认处理器
        else if(empty($urlParam)){
            $runFile = getConfig('DEFAULT_MODULE')."/".getConfig("DEFAULT_C_LAYER").'/'.getConfig('DEFAULT_CONTROLLER').getConfig('CONTROLLER_PEFEX').EndPrefex;
            $file = APP_PATH.$runFile;
            is_file($file) ? : die(getError(getLanage("_MODULE_NOT_EXIST_").":".getConfig('DEFAULT_MODULE')));
            define("__MODULE__",  getConfig('DEFAULT_MODULE'));
            define("__CONTROLLER__", getConfig('DEFAULT_CONTROLLER').$controllerPerfex);
            define("__ACTION__",  getConfig('DEFAULT_ACTION'));
        } else {
            //处理url参数 m=Home&c=Index&a=Index&b=a&dd=ee
            if (strpos($urlParam, $model)===0 &&!empty($model)&&$urlMode === 1){
                 $param =  parseURLParam($urlParam);
                 count($param) < 3 ? die(getError('url参数错误')) :true;
                 $path = APP_PATH.$param['m'].'/'. getConfig('DEFAULT_C_LAYER').'/'.$param['c'].$controllerPerfex.EndPrefex;
                 define("__MODULE__", $param['m']);
                 define("__CONTROLLER__", $param['c'].$controllerPerfex);
                 define("__ACTION__", $param['a']);
                 is_file($path) ? : die(getError(getLanage("_MODULE_NOT_EXIST_").":".__MODULE__));
            }
        }
        Hook::listenTag("module_check");
        is_file(APP_PATH.__MODULE__.'/Common/function.php') ? (include_once  APP_PATH.__MODULE__.'/Common/function.php' ): false;
        is_file(APP_PATH.__MODULE__.'/Config/config.php') ? getConfig(include_once APP_PATH.__MODULE__.'/Config/config.php') : false;
        getConfig('MODULE_CACHE' , APP_CACHE_PATH.__MODULE__.'/');//模板缓存路径
        self::loadFileSystem(APP_COMMON_PATH);
        return true;
    }
    /**
     * 遍历文件目录 
     */
    private static function loadFileSystem($path, $file = null)
    {
        if (!empty(self::$filePath[$path][$file]))
        {
            return ;
        }
        $mydir = dir($path);
        while($file = $mydir->read())
        {
            if((is_dir($path.'/'.$file)) AND ($file!=".") AND ($file!="..") && in_array($file, array('Common', 'Config')))
            {
                self::loadFileSystem($path.'/'.$file, $file);
            }
            elseif (is_file($path.'/'.$file) && strrchr($file, '.') === '.php')
            {
                self::$filePath[$path][$file] = $file;
                include_once $path.'/'.$file;
            }
        }
        $mydir->close();
    }
}