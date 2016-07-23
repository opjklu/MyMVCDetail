<?php
namespace  MyMVC;

/**
 * 运行处理 
 */
class  Application
{
    /**
     * 应用初始化
     */
    public static function init()
    {
        //加载公共配置文件
        loadExtFile(APP_COMMON_PATH);
       //url 分发
        Dispatcher::checkURL();
        
        //定义当前请求类型
        define("_IS_AJAX_",isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest" ? true :false) ;
        
        //结束url调度
        Hook::listenTag('url_dispather');
        //日志及混合编译目录转换为据对路径
        getConfig(APP_LOGS_PATH , realpath(APP_LOGS_PATH)."/");
        getConfig("TMPL_EXCEPTION_FILE" , realpath(getConfig("TMPL_EXCEPTION_FILE")));
    }
    /**
     *执行 
     */
    public static function exec()
    {
        if (defined("__MODULE__")&&defined("__CONTROLLER__")) {
            try {
                $model = __MODULE__.'\\'.getConfig('DEFAULT_C_LAYER').'\\'.__CONTROLLER__;
                $action = __ACTION__;
                if(!preg_match('/^[A-Za-z](\w)*$/',$action)){
                    // 非法操作
                    throw new \ReflectionException();
                }else {
                    $method = new \ReflectionMethod($model , $action);
                    if ($method->isPublic())
                    {
                        $class = new \ReflectionClass($model);
                        //前置操作
                        if($class->hasMethod('__before__'.$action))  {
                            $before = $class->getMethod('__before__'.$action);
                            $before->isPublic()? $before->invoke($class) : false;
                        }
                        //url参数绑定
                        if ($method->getNumberOfParameters()>0 && getConfig('URL_PARAMS_BIND')){
                            switch ($_SERVER['REQUEST_METHOD']) {
                                case 'POST':
                                    $value = array_merge($_POST , $_GET);
                                    break;
                                case 'PUT':
                                    parse_str(file_get_contents('php://input') , $value);
                                    break;
                               default:
                                    $value = $_GET;                                   
                            }
                            //获取参数
                            $params = $method->getParameters();
                            
                            //url 参数绑定的类型
                            
                            $urlType = getConfig('URL_PARAMS_BIND_TYPE');
                            
                            //循环参数
                            foreach ($params as $key => $name) {
                                $paramName = $method->getName();
                                if (1 == $urlType && !empty($value)){//按循序放入函数
                                    $functionParam[] = array_shift($value);
                                }elseif (0 == $urlType && !empty($value) && isset($value[$paramName])){
                                    $functionParam[] = $value[$paramName];
                                }elseif ($paramName->isDefaultValueAvailable()){
                                   $functionParam[] = $paramName->getDefaultValue();
                                } else {
                                    getError(getLanage('_PARAM_ERROR_').$paramName);
                                }
                            }
                            $method->invoke($class->newInstance(null) , $value);
                        }else { 
                           
                            $method->invoke($class->newInstance(null));
                        }
                        //后置操作
                        if($class->hasMethod('__front__'.$action))  {
                            $front = $class->getMethod('__front__'.$action);
                            $front->isPublic()? $front->invoke($class) : false;
                        }
                    } else{
                        throw new \ReflectionException();
                    }
                }
            }catch (\ReflectionException $e){
                $classData = new \ReflectionMethod($model,'__call');    
                $classObj = new \ReflectionClass($model);
                
                $classData->invokeArgs($classObj->newInstance(null), array($action,''));
            }
            return '';
        }
    }
    /**
     * 运行
     */
    public static  function run()
    {
        //初始化
        Hook::listenTag('application_init');
        self::init();
        
        //监听开始标签
        Hook::listenTag('tag_start');
        if (!IS_CLI)
        {
            //sessionc初始化
        }
        //记录运行时间
        getMemory('start_time');
        
        self::exec();
        
        Hook::listenTag('application_end');
    }
}