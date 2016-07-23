<?php
namespace MyMVC;
/**
 *模板解析类
 *@author 王强
 */
class Template
{
    /**
     *标签库数组
     *@var array
     */
    protected   $tagLib = array(); 
    
    /**
     *当前模板文件
     *@var string 
     */
    protected  $thisTemplateFile = '';
    
    /**
     * 筛选 literal
     * @var array
     */
    protected $literal = array();
    
    /**
     * 配置 config
     * @var array
     */
    protected $config  = array();
    
    /**
     * block 标签
     */
    protected  $block  = array();
    
    /**
     *模板变量 
     */
    protected $tVar   = array();
    
    /**
     *架构函数 
     */
    public function __construct()
    {
        $this->config['cachePath']         =   getConfig('MODULE_CACHE');
        $this->config['templateSuffix']    =   getConfig('TMPL_TEMPLATE_SUFFIX');
        $this->config['cacheSuffix']       =   getConfig('TMPL_CACHFILE_SUFFIX');
        $this->config['tmplCache']         =   getConfig('TMPL_CACHE_ON');
        $this->config['cacheTime']         =   getConfig('TMPL_CACHE_TIME');
        $this->config['taglibBegin']       =   $this->stripPreg(getConfig('TAGLIB_BEGIN'));
        $this->config['taglibEnd']         =   $this->stripPreg(getConfig('TAGLIB_END'));
        $this->config['tmplBegin']         =   $this->stripPreg(getConfig('TMPL_L_DELIM'));
        $this->config['tmplEnd']           =   $this->stripPreg(getConfig('TMPL_R_DELIM'));
        $this->config['defaultTmpl']       =   getConfig('TEMPLATE_NAME');
        $this->config['layoutItem']        =   getConfig('TMPL_LAYOUT_ITEM');
    }
    
    /**
     *转义模板运算标签
     *@access private
     *@author 王强 
     */
    private function stripPreg($str) 
    {
        return str_replace(
            array('{','}','(',')','|','[',']','-','+','*','.','^','?'),
            array('\{','\}','\(','\)','\|','\[','\]','\-','\+','\*','\.','\^','\?'),
            $str);        
    }
    
    /**
     *设置模板变量 
     */
    public function __set($name, $value)
    {
        $this->tVar[$name] = $value;
    }
    /**
     *获取模板变量 
     */
    public function __get($name)
    {
        return isset($this->tVar[$name]) ? null : $this->tVar[$name];
    }
    
    /**
     *加载解析模板 
     *@param string $templateFile 文件名
     *@param string $templateVar 模板变量
     *@param string $suffix      模板前缀
     *@return string
     */
    public function parseTemplateFile($templateFile , $templateVar = NULL, $perfix = null)
    {
        
        $this->tVar = $templateVar;
        
        //读取编译后的文件
        
        $templateCacheFile = $this->loadTemplateAndCache($templateFile, $perfix);
        
        Storage::load($templateCacheFile, $this->tVar, null, 'tpl');
        
    }
    
    /**
     *加载模板并编译缓存 
     */
    public function loadTemplateAndCache($templateFile, $perfix = null) 
    {
       //是否是文件
       if (is_file($templateFile))
       {
           $this->thisTemplateFile = $templateFile;
           $fileContent = file_get_contents($templateFile);
       }
       elseif (is_string($templateFile))
       {
           $fileContent = $templateFile;
       }
       $templateCacheName = $this->config['cachePath'].$perfix.md5($templateFile). $this->config['cacheSuffix'];
       
       //是否启用布局
       if(getConfig('LAYOUT_ON'))
       {
           if (false !== strpos($fileContent, '{__NO_LAYOUT__}'))
           {
               $fileContent = str_replace('{__NO_LAYOUT__}', '', $fileContent);
           }
           else 
           {
               $themeFile = THEME_PATH.getConfig('LAYOUT_NAME').$this->config['cacheSuffix'];
               is_file($themeFile) ? null : getError(getLanage('_FILE_NOT_EXITS').':'.$themeFile);
               $fileContent = str_replace( $this->config['layoutItem'], $fileContent, file_get_contents($themeFile));
           }
       }
       
       //编译文件
       $fileContent = $this->complier($fileContent);
       //模板替换输出
       Hook::listenTag('template_filter', $fileContent);
       //写入缓存文件
       Storage::putFile($templateCacheName, trim($fileContent), 'tpl');
       return $templateCacheName;
    }
    
    /**
     * 编译模板内容
     * @param string $content
     * @return string 
     */
    public function complier($content)
    {
        //模板解析
        $content = $this->parse($content);
        
        //还原被替换的literal
        $content = preg_replace_callback('/<!--###literal(\d+)###-->/is', array($this, 'reductionLiteral'), $content);
        //添加安全代码
        $content = '<?php defined("MyMVC_PATH") or die("No Resouse");?>'.$content;
        
        //优化生成的php代码
        $content = str_replace('?><?php', '', $content);
        
        //输出内容
        
        return strip_space($content);
    }
    
    /**
     * 模板解析 
     * @param string $content 模板内容
     * @return string
     */
    public function parse($content)
    {
        if (empty($content)) return null;
        //标签 开始、结束
        $start = $this->config['taglibBegin'];
        $end   = $this->config['taglibEnd'];
        //解析include标签
        $content = $this->parseInclude($content, $start, $end);
        //解析php语法
        $content = $this->parsePHP($content);
        
        //解析模板标签
        
        //step 1: 是否加载了标签库 以后做
        //是否检测标签库
        if (getConfig('TAGLIB_LOAD'))
        {
            $this->getIncludeTagLib();
        }
        
        // step 2:额外加载的标签库 以后完善 
        
        // step 3:系统自动加载得标签库
        if ($tags = getConfig('TAGLIB_BUILD_IN'))
        {
            //解析模板标签
            foreach (explode(',', $tags) as $value)
            {
                
            }
        }
        
        return $content;
    }
    /**
     * 解析标签库 
     * @return void 
     */
    protected function getIncludeTagLib()
    {
        //以后完善
    }
    /**
     * 解析include 语法 
     * @param string  $content 模板内容
     * @param string  $start   模板开始标签
     * @param string  $end     模板结束标签
     * @param boolean $isParseExtend 是否解析继承标签
     * @return string
     */
    
    public function parseInclude($content,$start, $end, $isParseExtend = true)
    {
        if ($isParseExtend === true)
            $content = $this->parseExtend($content);
        //解析布局 先不做了
        
        //解析include
        $reg = '/'.$start.'include\s(.+?)\/'.$end.'/is'; // 匹配include 标签的属性 file='library:header'
        $include = preg_match_all($reg, $content, $matchs);
     
        if ($include)
        { 
            for ($i = 0; $i <= $include-1; $i++)
            {
                $xmlData = $matchs[1][$i];
                //解析xml属性
                $array   = $this->xmlAttribute($xmlData);
                $file    = $array['file'];
                unset($array['file']);
                $content = str_replace($matchs[0][$i], $this->loadTemplateAndCacheFile($file, $array,$start, $end, $isParseExtend), $content);
            }
        }
       
        return $content;
    }
    
    /**
     * 检查php语法
     * @param string $content 模板内容
     * @return string;
     */
    protected  function parsePHP($content)
    {
        //是否开启短标签模式
        if (ini_get('short_open_tag'))
        {
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\';\n',$content);
        }
        
        if(getConfig('TMPL_DENY_PHP') && false !== strpos($content, '<?php'))
        {
            getError(getLanage('_NOT_ALLOW_PHP_'));
        }
        return $content;
    }
  
    /**
     *记录页面中的block标签 
     *@param array | string $name    block 属性名称
     *@param string $content 模板内容
     *@access private
     *@return null   
     */
    private function saveBlockNum($name, $content = '')
    {
        /**示例
         *  Array
         (
         [0] => kjkljakljkl
         [1] => space
         [2] => kjkljakljkl
         )
         */
        if (is_array($name))
        {
            $content = $name[2];
            $name    = $name[1];
        }
        $this->block[$name] = $content;
        
        return null;
    }
    
    /**
     *替换 literal 标签
     *@access private
     *@param string | array $content 模板内容
     *@return string | false
     */
    private function replaceLiteral($content)
    {
        if (is_array($content)) $data = $content[1];
        if (trim($content) == '') return false;
        $parseStr = '<!--###literal###-->';
        
       $this->literal[count($this->literal)] = $data;
       
       return $parseStr;
    }
    
    /**
     *还原 literal 标签
     *@access private
     *@param string | array $content 模板内容
     *@return string | false
     */
    private function reductionLiteral($tag)
    {
        if (is_array($tag)) $data = $tag[1];
        if (trim($tag) == '') return false;
        
        $literal = $this->literal[$tag];
        
        unset($this->literal[$tag]);
         
        return $literal;
    }
    
    /**
     * 分析xml属性
     * @param string $attr 属性
     * @return array
     */
    private function xmlAttribute($attr)
    {
       $xml = '<tpl><tag '.$attr.' /></tpl>';
       //获取xml对象
       try {
            $xmlObj     = new \SimpleXMLElement($xml);
            $attributes = (array)$xmlObj->tag;
            $xmlData    = array_change_key_case($attributes['@attributes']);
            unset($xmlObj);
       }catch (Error $e){
           getError(getLanage('_XML_TAG_ERROR_').':'.$xml);
       }
       return $xmlData;
    }
    
    /**
     * 允许加载的模板文件 
     * @param string $templateFile 模板文件
     * @access private
     * @return string
     */
    private function loadTemplateFileTrue($templateFile)
    {
        if (0 === strpos($templateFile, '$'))
        {
            //
            $substrName = substr($templateFile, 1);
            
            $templateFile   = $this->$substrName;
        }
        $parStr = null;
        $array = explode(',', $templateFile);
        foreach ($array as $key => &$value)
        {
            if (empty($value))continue;
            if (false === strpos($value, $this->config['templateSuffix']))
            {
                $value = loadTemplateFile($value);
            }
            $parStr.=file_get_contents($value);
        }
        return $parStr;
    }
    
    /**
     * 加载公共模板并缓存 
     * @param string  $templateContent 模板内容
     * @param array   $var             模板变量
     * @param boolean $extend          是否解析继承
     * @param string  $start           模板开始标签
     * @param string  $end             模板结束标签
     * @return string
     */
    private function loadTemplateAndCacheFile($templateContent, array $var = array(), $start, $end,$extend)
    {
        //获取 要加载得文件
        $templateContent = $this->loadTemplateFileTrue($templateContent);
        //替换模板变量
        foreach ($var as $key => $value)
        {
            $templateContent = str_replace('['.$key.']', $value, $templateContent);
        }
        //再次对模板进行包含分析
        
        return $this->parseInclude($templateContent, $start, $end, $extend);
    }
    
    /**
     * 解析继承标签 
     * @param string $templateContent
     * @return string;
     * @access protected
     */
    protected function parseExtend($templateContent) 
    {
        //标签 开始、结束
        $start = $this->config['taglibBegin'];
        $end   = $this->config['taglibEnd'];
        
       //匹配继承标签
       $find = preg_match('/'.$start.'extend\s(.+?)\s*?\/'.$end.'/is', $templateContent, $matchs);
      
       if ($find)
       {
           //替换extend 字符串
           $content = str_replace($matchs[0], '', $templateContent);
           
           //记录 内容中的block标签
           preg_replace_callback('/'.$start.'block\sname=[\'"](.+?)[\'"]\s*?'.$end.'(.*?)'.$start.'\/block'.$end.'/is', array($this, 'saveBlockNum'),$content);
           //解析xml属性
           $xmlData = $this->xmlAttribute($matchs[1]);
           
           //解析模板文件
           $content = $this->loadTemplateFileTrue($xmlData['name']);
          
           ////对继承模板中的include 所包含的文件将进行分析
           $content = $this->parseInclude($content,$start, $end, false);
            
//            $content = 
          // 替换block标签
          $content = $this->replaceBlock($content);
       }
       else 
       {
           $content = preg_replace_callback('/'.$start.'block\sname=[\'"](.+?)[\'"]\s*?'.$end.'(.+?)'.$start.'\/block'.$end.'/is', function()use($matchs){
               return stripcslashes($matchs[2]);
           }, $templateContent);
       }
   
       return $content;
    }
    
    
    /**
     * 替换继承模板得block标签
     * @param string $content 模板内容
     * @return string
     */
    protected function replaceBlock($content)
    {
        static $parse = 0;
        //标签 开始、结束
        $start = $this->config['taglibBegin'];
        $end   = $this->config['taglibEnd'];
        
        //匹配block
        $reg = '/('.$start.'block\sname=[\'"](.+?)[\'"]\s*?'.$end.')(.*?)'.$start.'\/block'.$end.'/is';
        
        if (is_string($content))
        {
            do
            {
                $content = preg_replace_callback($reg, array($this, 'replaceBlock'), $content);
            }while($parse && $parse--);
            return $content;
        }
        elseif (is_array($content))
        {
            /**
             *  Array
                (
                     [0] => ccccccccccccccccccccccccccccccccccc
                     [1] =>
                     [2] => add
                     [3] => ccccccccccccccccccccccccccccccccccc
                )
             */
            //存在嵌套
            if (preg_match('/'.$start.'block\sname=[\'"](.+?)[\'"]\s*?'.$end.'/is', $content[3] ))
            {
                $parse = 1;
                $content[3] = preg_replace_callback($reg, array($this, 'replaceBlock'), "{$content[3]}{$start}/block{$end}");
                return $content[1].$content[3];
            }
            else 
            {
                $name    = $content[2];
                $content = isset($this->block[$name]) ? $this->block[$name] : $content[3];
                return $content;
            }
        }
    }
}