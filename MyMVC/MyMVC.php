<?php
use MyMVC\MyMVC;
/**
 * @author 王强
 * 框架核心配置
 */
 
//记录开始运行内存 和时间
define("MEMORY_USE", function_exists("memory_get_usage"));
MEMORY_USE ? $GLOBALS["memery"] = memory_get_usage() : false;

//设置默认时区
// date_default_timezone_set("PRC");
// $GLOBALS["startTime"] = time();
//框架版本
const MYMVC_VERSION = "1.0";
//检测php版本
PHP_VERSION >="5.3" ? true : die("系统所需的php版本必须高于或者等于5.3");
if (version_compare(PHP_VERSION, "5.4", "<")){
    ini_set("magic_quotes_runtime", 0);
    define("MAGIC_QUOTE", get_magic_quotes_gpc() ? true : false);
}
//开始定义应用
defined("APP_NAME") or define("APP_NAME", "Application");
defined("APP_PATH") or define("APP_PATH",  dirname($_SERVER['SCRIPT_FILENAME']).'/');
defined("APP_STATUS") or define("APP_STATUS", "");
defined("APP_DEBUG") or define("APP_DEBUG" , false);
//定义框架路径信息
defined("MyMVC_PATH") or define("MyMVC_PATH", __DIR__."/");//入口
defined("LIBRARY_PATH") or define("LIBRARY_PATH", MyMVC_PATH."Library/");//核心类库
defined("FUNCTION_PATH") or define("FUNCTION_PATH", MyMVC_PATH."functions/");//核心函数库
defined("CONFIG_PATH") or define("CONFIG_PATH", MyMVC_PATH."Config/");//核心配置文件
defined("MODE_PATH") or define("MODE_PATH", MyMVC_PATH."Mode/");//应用模式
defined("TPL_PATH") or define("TPL_PATH", MyMVC_PATH."Tpl/");//默认提示文件

defined("BEHAVIOR_PATH") or define("BEHAVIOR_PATH", LIBRARY_PATH."Behavior/");//行为类库
defined("CORE_PATH") or define("CORE_PATH", LIBRARY_PATH."MyMVC/");//框架执行核心
defined("VENDOR_PATH") or define("VENDEOR_PATH", LIBRARY_PATH."Vendor/");//第三方类库
defined("OT_PATH") or define("OT_PATH", LIBRARY_PATH."OT/");//

defined("RUNTIME_PATH") or define("RUNTIME_PATH", APP_PATH."Runtime/");//运行时目录
defined("APP_CACHE_PATH") or define("APP_CACHE_PATH",RUNTIME_PATH."cache/");//缓存目录
defined("APP_DATA_PATH") or define("APP_DATA_PATH", RUNTIME_PATH."Data/");
defined("APP_LOGS_PATH") or define("APP_LOGS_PATH",RUNTIME_PATH. "log/");
defined("APP_COMMON_PATH") or define("APP_COMMON_PATH", APP_PATH."Common/");
defined("APP_CONFIG_PATH") or define("APP_CONFIG_PATH", APP_COMMON_PATH."Config/");
//URL模式
const URL_PATHINFO = 0;
const URL_REWRITE    = 1;
const URL_PT = 2;
const URL_COMMON = 3;
//自动识别sae
if (function_exists("setAutoLoader")){
    defined("APP_Mode")or define("APP_MODE", "sae");
    defined("STROGE_TYPE") or define("STROGE_TYPE", "sae");
}else {
    defined("APP_MODE")or define("APP_MODE", "Config");
    defined("STROGE_TYPE") or define("STROGE_TYPE", "File");
}

define("IS_WINDOW", strstr(PHP_OS, "WIN") ? true : false);
define("IS_CGI", substr(PHP_SAPI, 0 , -3) ? true : false);
define("IS_CLI", PHP_SAPI == "cli" ? true : false);
//CGI（通用网关接口 / Common Gateway Interface）
//CLI（命令行运行 / Command Line Interface）
//判断是否是命令行模式
if (!IS_CLI){
    //当前文件名
    if (defined("_PHP_FILE_")){
        if (IS_CGI){
            //例如 http://www.baidu.com/index.php/ PHP_SELF => index.php
           $tmp = explode(".php" , $_SERVER["PHP_SELF"]);//$_SERVER['PHP_SELF'] 表示当前 php 文件相对于网站根目录的位置地址，与 document root 相关。
           
           define("_PHP_FILE_", rtrim(str_replace($_SERVER["HTTP_HOST"], "", $tmp[0].".php") , "/"));
        }else {
            define("_PHP_FILE_", rtrim($_SERVER["SCRIPT_NAME"]));
        }
    }
    
    //当前网站地址
    if (defined("__ROOT__")){
        $root = rtrim(_PHP_FILE_ , "/");
        define("__ROOT__", ($root == "/" || $root=="\\") ? "" : $root );
    }
}
const EndPrefex = ".class.php";
//载入核心文件
require_once CORE_PATH."MyMVC".EndPrefex;
MyMVC::runMyMVC();