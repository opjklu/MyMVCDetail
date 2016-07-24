<?php
defined("MyMVC_PATH") or exit("No Resource");
/**
 *获取配置信息 
 *@param $key 配置变量
 *@param $value 配置值
 *@param $default 默认值
 *@return 配置值
 */
function getConfig($key=NULL , $value = NULL , $default = NULL)
{
    static $config = array();
    //无参数时获取所有
    if (empty($key)){
        return $config;
    }
    //优先配置和获取值
    if (is_string($key)){
        if (!strpos(".", $key)){
            if (is_null($value))
                return  isset($config[$key]) ? $config[$key] : $default;
            $config[$key] = $value;
            return ;
        }
        //如果存在点 表示支持二维数组
        /**
         *如array(
         *    "key" => array(
         *         'a'=>"aaa",
         *         'b'=>"bbb"
         *    )
         *) 
         */
        $arrayKeyValue = explode(".", $key);
        $arrayKeyValue[0] = strtolower($arrayKeyValue[0]);
        if (is_null($value))
            return isset($config[ $arrayKeyValue[0] ] [ $arrayKeyValue[1]] )? $config[$arrayKeyValue[0]][$arrayKeyValue[1]] : $default;
        $config[$arrayKeyValue[0]][$arrayKeyValue[1]] = $value;
        return ;
    }
    if (is_array($key)){
        $config = array_merge($config , array_change_key_case($key , CASE_UPPER));
        return ;
    }
    return null;
}
/**
 *URL重定向
 *@param $url 支持多行url
 *@param $time 几秒后跳转 
 *@param $message 提示信息
 */
function redirect($url , $time=0 , $message = "")
{
    //多行url支持
    $url = str_replace(array("\r" , "\n"), '', $url);
    empty($message) ? "系统将在{$time}秒后跳到{$url}" : $message;
    //headers_sent() 函数检查 HTTP 标头是否已被发送以及在哪里被发送。如果报头已发送，则返回 true，否则返回 false。
    if (!headers_sent()){
        !empty($url) ? header("refresh:{$time};Location:{$url}"): false;
        print_r($message);
        exit();
    }else {
        header("<meta http-equiv='refresh' content='{$time};url={$url}' />");
        $time === 0?exit():showData($message);
    }
}
/**
 * 编译文件
 * @param string $filename 文件名
 * @return string
 */
function compile($filename) 
{
    $content    =   php_strip_whitespace($filename);//strip_whitespace() 函数返回已删除 PHP 注释以及空白字符的源代码文件。
    $content    =   trim(substr($content, 5));
    // 替换预编译指令
    $content  = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    $content  = 0===strpos($content,'namespace') ?  preg_replace('/namespace\s(.*?);/','namespace \\1{',$content,1) :  'namespace {'.$content;
    $content  = '?>' == substr($content, -2) ? substr($content, 0, -2) : $content;
    return $content.'}';
}
/**
 *获取内存及其app运行时间
 *@param $start 开始运行
 *@param $end 结束运行
 *@param $dec 显示小数位
 */
function getMemory($start , $end = "" , $dec = 6)
{
    static $memory = array();//内存使用状况
    static $appInfo = array();//app主要信息
    //统计时间
    is_float($end) ? $appInfo[$end] = $start : false;
    //记录内存
    if (!empty($end)){
        !isset($appInfo[$end]) ? $appInfo[$end] = microtime(true) : false;//获取时间
        return MEMORY_USE&&$dec == "m" ? (!isset($memory[$end]) ? number_format( (($memory[$end]=memory_get_usage())-$memory[$start])/1024 ) : false ) 
         : number_format( (($memory[$end]=memory_get_usage())-$memory[$start]),$dec );
    }else {//记录内存与时间
        MEMORY_USE ? $memory[$start] = memory_get_usage() : false;
        $appInfo[$start] = microtime(true);
    }
}

/**
 * 添加和获取页面Trace记录
 * @param string $value 变量
 * @param string $label 标签
 * @param string $level 日志级别
 * @param boolean $record 是否记录日志
 * @return void
 */
function getTrace($value="[MyMVC]" , $lable="" , $level = "DEBUG", $record = FALSE)
{
    return MyMVC\MyMVC::getTrace($value , $lable , $level , $record);
}
/**
 * 输出错误
 */
 function getError($message , $code=null)
 {
     throw new  MyMVC\Error($message , $code);
 }
/**
 *获取语言包 
 */
function getLanage($name =null , $value = null)
{
    static $lanage = array();
    if (empty($name)) return $lanage;
    if (is_string($name)){
        $name = strtoupper($name);
        if (is_null($value)){
            return isset($lanage[$name]) ? $lanage[$name] : $name;
        }elseif (is_array($value)){
            $replace = array_keys($value);
            foreach ($replace as &$val){
                $val = '{$'.$val."}";
            }
            $content = str_replace($replace, $value, isset($lanage[$name]) ? $lanage[$name] : $name );
            return $content;
        }
        $lanage[$name] = $value;
    }//批量定义

     is_array($name) ? $lanage = array_merge($lanage , array_change_key_case($name , CASE_UPPER)) : false;
    return ;
}
/**
 *去除注释及其空白 
 */
function strip_space($content)
{
    $strip = '';//接受处理后的代码
    //分析源代码
    $content = token_get_all($content);
    $last_space = false;
    
    for ($i = 0 , $j= count($content); $i <$j; $i++) {
        if (is_string($content[$i])){
            $strip .= $content[$i];
            $last_space = false;
        }else {
            switch ($content[$i][0]){
                //过滤各种注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                case T_WHITESPACE://去除空格
                    if (!$last_space){
                        $strip .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $strip .="<<<MyMVC\n";
                    break;
                case T_END_HEREDOC:
                    $strip .= "MyMVC\n";
                    for ($k = $i+1 ; $k < $j ; $k++){
                        if ($content[$k] == ";" && is_string($content[$k])){
                            $i = $k;
                            break;
                        }elseif ($content[$i][0] == T_CLOSE_TAG){
                            break;
                        }
                    }
                    break;
                default:
                    $strip .= $content[$i][1];
                    $last_space = false;
                    break;
            }
        }
    }
    return $strip;
}
/**
 *加载应用文件 
 */
function loadExtFile($path)
{
    //是否定义了自动加载得文件
    if ($customFile = getConfig("__CUSTOM_FILE__")){
      foreach ($customFile as $key => $value){
          $file = $path."Common/".$value.".php";
          is_file($file) ? require_once $file : false; 
      }
    }
    //是否定义了 配置文件 数组
    if ($customConfigFile = getConfig("__CUSTOM_CONFIG_FILE__")){
        foreach ($customFile as $key => $value){
            $file = $path."Config/".$value.".php";
            is_file($file) ? require_once $file : false;
        }
    }
}
/**
 *分析传统url 参数 
 *m=aaa&c=a&a=s&c=b 此样式
 *@param  string $urlParam url 参数
 *@param  string $splice   分隔符
 *@param  string $action   操作方法得 默认接受参数
 */
function parseURLParam($urlParam , $splice = "&" , $action='a')
{
    $spliceParam = strstr($urlParam, $splice.$action);
    strrpos($spliceParam, $splice.getConfig("VAR_MODULE"))|| 
    strrpos($spliceParam, $splice.getConfig("VAR_CONTROLLER")) || 
    strrpos($spliceParam, $splice.getConfig("VAR_ACTION"))? die(getError("参数重复出现")) : false ;
     static $parseParam = array();
     if (strpos( $urlParam , $splice)){
         foreach (explode($splice,$urlParam) as $key => $value)
         {
               parseURLParam($value , "=");
               $key % 2 ===0 ? $baseParam[] = $value : ($key % 2 === 1 ?   $param[] = $value : false); 
         }
         foreach ($baseParam as $keyValue => $valueKey){
             if (!empty($param[$keyValue]))
             {
                 if ($keyValue%2 === 0){
                     $parseParam[$valueKey] = $param[$keyValue];
                     
                 }elseif ($keyValue %2 === 1){
                     $parseParam[$valueKey] = $param[$keyValue];
                 }
             }
         }
         unset($baseParam);
         unset($param);
         foreach ($parseParam as $k => $v ){
             if (strpos($k,'=')){
                 unset($parseParam[$k]);
             }
         }
         return $parseParam;
     }else {
         return null;
     }
}
/**
 *输出信息 
 */
function showData($data , $isDie = false)
{
    $fileData = debug_backtrace();
    ob_start();
    print_r($data);
    $info['content'] =ob_get_clean();
    $str = '<pre style="padding:10px;border-radius:5px;background:#F5F5F5;border:1px solid #aaa;font-size:14px;line-height:18px;">';
    $str .= "\r\n";
    $str .= '<strong>FILE</strong>: ' . $fileData[0]['file'] . " <br />";
    $str .= '<strong>LINE</strong>: ' . $fileData[0]['line'] . " <br />";
    $str .= '<strong>TYPE</strong>: ' . gettype($data) . " <br />";
    $str .= '<strong>CONTENT</strong>: ' . trim($info['content'], "\r\n");
    $str .= "\r\n";
    $str .= "</pre>";
    echo $str;
    $isDie === false ? false : die();
}
/**
 *cookie 设置 获取
 *@param string  $key cookie 名称
 *@param string  $value cookie 值
 *@param array   $options cookie 配置
 *@return string || array || null
 */
 function cookie($key = null , $value = null , array $options = null)
 {
     $config = array(
        'expire' => getConfig('COOKIE_EXPIRE'),  // cookie 有效期
        'perfix' => getConfig('COOKIE_PREFIX'), //  cookie 前缀
        'domain' => getConfig('COOKIE_DOMAIN'), //  cookie 有效域名
        'path'   => getConfig('COOKIE_PATH')    // cookie 路径
     );
     //是否覆盖默认设置
     if (!empty($options)){
         $arrayKey = array_keys($config);
         foreach ($options as $key => $valueData){
             if (!array_key_exists($valueData, $arrayKey)){
                 getError('键'.$valueData.'在数组options不存在' , 1);
                 die();
             }
         }
         $config = array_merge($config , array_change_key_case($options , CASE_LOWER));
     }
     //删除指定前缀的所有cookie
     if (empty($key)){
         if (empty($_COOKIE)){
             return  null;
         }
         $name = empty($value) ? $config['perfix'] : $value; //如果不指定
         if (!empty($name)) {
             foreach ($_COOKIE as $keyOfCookie => $valueOfCookie) {
                 if (0 === strpos($keyOfCookie, $name)){
                     setcookie($keyOfCookie , '' , time()-3600 ,$config['path'] , $config['domain']  );
                     unset($_COOKIE[$keyOfCookie]);
                 }
             }
         } else {
            return  null;
         }
     }
     //获取cookie
     $name = $config['perfix'].$key;
     if ('' === $value) {
         if ( !empty($name) && !empty($_COOKIE[$name])){
             $value = $_COOKIE[$name];
             return 0 === strpos($value, 'MyMVC_') ? array_map('urldecode', json_decode( MAGIC_QUOTE? stripcslashes(substr($value, 6)) : $value  , true)) : $value;
         }else {
             return null;
         }
     } else { //cookie 赋值
        if (!empty($value)) {
            setcookie($name , '' , time()-3600 , $config['path'] , $config ['domain']);
            unset($_COOKIE[$name]);
        } else {
            $value = is_array($value) ? 'MyMVC_'.json_encode(array_map('urlencode', $value)) : $value;
            $expire = !empty($config['expire']) ? $config['expire']+time() : 3600;
            setcookie($name , $value , $expire , $config['path'] , $config['domain']);
            $_COOKIE[$name] = $value;
        }
     }
 }
 /**
  * 赋默认值
  * @param array  $array     要设置的数组
  * @param array  $setKey    要设置的键
  * @param mixed  $default   默认值
  * @param string $isDiffKey 特殊的键
  * @return array
  */
 function isSetDefaultValue(array &$array, array $setKey, $default = null, $isDiffKey = 'page')
 {
     if (empty($setKey))
     {
         return null;
     }
     foreach ($setKey as $value)
     {
         if (!array_key_exists($value, $array) && $value != $isDiffKey)
         {
             $array[$value] = $default;
         }
         elseif (!isset($array[$value]))
         {
             $array[$value] = 1;
         }
     }
     return $array;
 }
/**
 * 获取模版文件 格式 资源://模块@主题/控制器/操作
 * @param string $name 模版资源地址
 * @param string $layer 视图层（目录）名称
 * @return string
 */
 function loadTemplateFile($template=null, $layer=null)
 {
     // 解析模版资源地址
     if(false === strpos($template,'://')){
         $template   =   'http://'.str_replace(':', '/',$template);
     }
     $info   =   parse_url($template);
     $file   =   $info['host'].(isset($info['path'])?$info['path']:'');
     $module =   isset($info['user'])?$info['user'].'/':__MODULE__.'/';
     $extend =   $info['scheme'];
     $layer  =   $layer?$layer:getConfig('DEFAULT_V_LAYER');
 
     // 获取当前主题的模版路径
     if($view_path = getConfig('VIEW_PATH')){ // 指定视图目录
         $baseUrl    =   $view_path.$module.'/';
     }else{
         $baseUrl    =   APP_PATH.$module.$layer.'/';
     }
 
     // 获取主题
     $theme  =   substr_count($file,'/')<2 ? getConfig('DEFAULT_THEME') : '';
 
     // 分析模板文件规则
     $depr   =   getConfig('TMPL_FILE_DEPR');
     if('' == $file) {
         // 如果模板文件名为空 按照默认规则定位
         $file = __CONTROLLER__ . $depr . __ACTION__;
     }elseif(false === strpos($file, '/')){
         $file = __CONTROLLER__ . $depr . $file;
     }elseif('/' != $depr){
         if(substr_count($file,'/')>1){
             $file   =   substr_replace($file,$depr,strrpos($file,'/'),1);
         }else{
             $file   =   str_replace('/', $depr, $file);
         }
     }
     return $baseUrl.($theme?$theme.'/':'').$file.getConfig('TMPL_TEMPLATE_SUFFIX');
 }