<?php
namespace MyMVC; 
defined("MyMVC_PATH") or exit("No Resource");
/**
 *核心入口
 */
class MyMVC
{
    private static $map=array();//类别名
    
    private static $initnation = array();//实例化对象
    
    /**
     *入口函数 
     */
    public static  function runMyMVC()
    {
        //注册autoload
        spl_autoload_register('MyMVC\MyMVC::autoload');
      
        //设定错误
        
        /**
         * PHP中有一个叫做  register_shutdown_function 的函数,可以让我们设置一个当执行关闭时可以被调用的另一个函数.
         * 也就是说当我们的脚本执行完成或意外死掉导致PHP执行即将关闭时,我们的这个函数将会 被调用 
         */
        register_shutdown_function('MyMVC\MyMVC::fetalError');
        //自定义异常处理
        set_exception_handler('MyMVC\MyMVC::customException');
        //初始化文件存储方式
        Storage::connect(STROGE_TYPE);
        $runFileName = RUNTIME_PATH.APP_MODE."~runfilename.php";
        if(!APP_DEBUG&&Storage::hasFile($runFileName)){
            Storage::load($runFileName);
        }else {
            Storage::hasFile($runFileName) ? Storage::unLink($runFileName) : false;
            $content = "";
            //加载核心配置文件
            $centerFile = include  is_file(APP_CONFIG_PATH."sae.php")?APP_CONFIG_PATH."sae.php" : MODE_PATH.APP_MODE.".php";
            foreach ($centerFile["core"] as $key => $value){//加载核心类
               is_file($value) ? include $value : false;
                 !APP_DEBUG ? $content .= compile($value) : false;
            }
            //加载配置文件
            foreach ($centerFile["config"] as $keyValue => $valueKey)
            {
                is_numeric($keyValue)&&is_file($valueKey) ? getConfig(include $valueKey) :false; 
            }
            //加载 应用模式
            if (APP_MODE != "Config" && is_file(APP_CONFIG_PATH."config_".APP_MODE.".php")){
                getConfig(include_once APP_CONFIG_PATH."config_".APP_MODE.".php");
            }
           if (isset($centerFile["alias"])){//加载别名定以
               self::addMap(is_array($centerFile["alias"])? $centerFile["alias"] : include_once $centerFile["alias"]);
           }
           if (is_file(APP_CONFIG_PATH."alias.php")){//应用别名定一
               self::addMap(include_once APP_CONFIG_PATH."alias.php");
           }
           if (isset($centerFile["tags"])){//加载行为模式
               Hook::import(is_array($centerFile["tags"]) ? $centerFile["tags"] : include $centerFile["tags"]);
           }
           if (is_file(CONFIG_PATH."tags.php")){//应用行为模式
               Hook::import(include CONFIG_PATH."tags.php");
           }
           //加载语言包
           getLanage(include MyMVC_PATH."Lang/".getConfig("DEFAULT_LANG").".php");
           if (!APP_DEBUG){
               $content .= "\nnamespace { MyMVC\MyMVC::addMap(".var_export(self::$map , true).");";
               // 返回关于传递给该函数的变量的结构信息 true 可赋值给变量
               $content .= "\ngetLanage(".var_export(getLanage() , true)."); \ngetConfig(".var_export(getConfig() , true)."); \nHook::getTag(".var_export(Hook::getTag(),true).");}";
               Storage::putFile($runFileName , strip_space("<?php ".$content) );
           }else {
               //加载调试配置
               getConfig(include CONFIG_PATH."debug.php");
               //判断是否存在应用配置
               is_file(APP_CONFIG_PATH."debug.php") ? getConfig( include APP_CONFIG_PATH."debug.php"): false;
           }
        }
        //读取应用状态配置文件
        APP_STATUS && is_file(APP_CONFIG_PATH.APP_STATUS.".php") ? getConfig(include APP_CONFIG_PATH.APP_STATUS.".php") : false;
        //设置默认时区
        date_default_timezone_set(getConfig("DEFAULT_TIMEZONE"));
        //判断是否自动创建目录
        if (!is_file(APP_LOGS_PATH)&&getConfig("CHECK_APP_DIR")){
            Build::autoBuildFile();
        }
        //记录文件加载时间
        getMemory("loadTime");
       //运行应用
       Application::run();
    }
    /**
     *自动加载 
     */
    public static function autoload($class)
    {
        if (isset(self::$map[$class])){
            require_once self::$map[$class];
        }else{
            $name = strstr($class, "\\" , true);//返回\之前的s实际类名MyMVC\MyMVC  '\\' 代表一个\
           
            if (in_array($name, array("MyMVC" , "Behavior" , "OT" , "Vendor")) || is_dir(LIBRARY_PATH.$name) ){
                $path = LIBRARY_PATH;//定位核心类库
            }else {
                $namespace = getConfig("AUTOLOAD_NAMESPACE");//是否自定义了命名空间
                $path = isset($namespace[$name]) ? dirname($namespace[$name]."/") : APP_PATH;
            }
            $filename = $path.str_replace("\\", "/", $class).EndPrefex;//
            //判断系统类型realpath() 函数返回绝对路径
            if (IS_WINDOW && false ===  strpos(str_replace("/", "\\", realpath($filename)), $class.EndPrefex) && is_file($filename)){
                //是否开启严格模式检测文件名大小写
                return ;
            }else {
                self::$map[$class] = $class;
                require_once $filename;
            }
        }
    }
    /**
     * 捕获致命错误
     */
    public static function fetalError()
    { 
        /**
         *Array
         *(
         *  [type] => 4
         *  [message] => syntax error, unexpected T_VARIABLE
         *  [file] => E:\wamp\wamp\www\MyShop\APP\Home\Controller\IndexController.class.php
         *  [line] => 8
        ) 
         */
        //保存日志--以后做好吧
        $error = error_get_last();
        if (is_null($error))return ;
        switch ($error["type"])
        {
            case E_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE://分析状态
            case E_USER_ERROR:
                ob_get_clean();
                self::printError($error);
                break;
        }
    }
    /**
     *输出错误 
     *@param string | array 错误数据
     *@return void 输出错误
     */
    private static function printError($error)
    {
       
        $e = array();
        if (APP_DEBUG || IS_CLI){ //是否开启了调试模式 命令行格式
            /**
            *返回: "->"  - 方法调用
            *返回: "::"  - 静态方法调用
            *返回 nothing - 函数调用
            *args	数组	如果在函数中，列出函数参数。如果在被引用的文件中，列出被引用的文件名。 
            *Array
                (
                    [0] => Array
                        (
                            [file] => E:\wamp\wamp\www\MyShop\ThinkPHP\Library\Think\Think.class.php
                            [line] => 270
                            [function] => halt
                            [class] => Think\Think
                            [type] => ::
                            [args] => Array
                                (
                                    [0] => Array
                                        (
                                            [type] => 4
                                            [message] => syntax error, unexpected T_VARIABLE
                                            [file] => E:\wamp\wamp\www\MyShop\APP\Home\Controller\IndexController.class.php
                                            [line] => 8
                                        )
                                )
                        )
                    [1] => Array
                        (
                            [function] => fatalError
                            [class] => Think\Think
                            [type] => ::
                            [args] => Array
                                (
                                )
                        )
                )
             */
            if (!is_array($error)){
                $errorValue =debug_backtrace();//
                foreach ($errorValue[0] as $key => $value){
                    $e[$key] = $value;
                }
                ob_start();
                debug_print_backtrace();
                $e["trace"] = ob_get_clean();
            }else{
                $e = $error;
            }
           // iconv — 字符串按要求的字符编码来转换
            IS_CLI?exit(iconv("UTF-8", "GBK", $e["message"].PHP_EOL."File".$e["file"]."({$e['line']})".PHP_EOL.$e['trace'])) : null;
            
        }else{ 
            //获取错误页面
            $errorBook = getConfig("ERROR_PAGE");
            !empty($errorBook) ? redirect($errorBook) : false;
            is_array($error) ? $e['message'] = $error["message"] : $e["message"] = $error;
        }
        $errorShow = getConfig("TMPL_EXCEPTION_FILE" , null , TPL_PATH."MyMVC_exception.tpl");
       
        include_once $errorShow;
        die();
    }
    /**
     *注册类别名 
     */
    public static function addMap($class , $tmp="")
    {
        is_array($class) ? self::$map = array_merge(self::$map , $class) : self::$map[$class] = $tmp;
    }
    /**
     *获取trace记录 
     */
    public static function getTrace($value="[MyMVC]" , $lable="" , $level = "DEBUG", $record = FALSE)
    {
        static $trace = array();
        if ($value === '[MyMVC]')
            return $trace;
        else{
            $content = ($lable ? $lable.":" : "").print_r($value , true);
            $value = strtoupper($level);
            //是否记录日志
            defined("IS_AJAX")&&IS_AJAX || getConfig("SHOW_PAGE_TRACE") || $record ? Log::record() : false;
            empty($trace[$level]) || count($trace[$level]) > getConfig("TRACE_MAX_RECORD") ? $trace = array() : $trace[$level][]=$content;
        }
    }
    /***
     *自定义异常处理 
     *@param Error $exception object 异常对象
     */
    public static function  customException($exception)
    {
        $error = array();
        $error['message'] = $exception->getMessage();
        $trace = $exception->getTrace();
        $error['line'] =  $trace[0]['function'] === 'getError' ? $trace[0]['line'] : $exception->getLine();
        $error['file']  =  $trace[0]['function'] === 'getError' ? $trace[0]['file']  : $exception->getFile();
        $error['trace'] = $exception->getTraceAsString();
        //记录日志
        
        self::printError($error);
    }
    /**
     *取得类的实例 
     *@param  string $class 类名
     *@param  string $method 静态方法名
     *@return Object
     */
    public static  function getIntrance($class , $method = '')
    {
        $identityClass = $class.$method;
        if (!empty(self::$initnation[$identityClass]))
        {
            return self::$initnation[$identityClass];
        }
        else
        {
            if (class_exists($class))
            {
                $obj = new $class();
                self::$initnation[$identityClass] = !empty($method)&&method_exists($obj, $method) ? call_user_func(array(&$obj , $method)) : $obj;
                
                return self::$initnation[$identityClass];
            }
            else 
            {
                self::printError(getError(getLanage('_CLASS_NOT_EXIST_')).':'.$class);
                return null;
            }
        }
    }
}