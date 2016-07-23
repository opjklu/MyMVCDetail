<?php
namespace  MyMVC;

/**
 *视图类 
 */
class  View
{
    /**
     *模板变量 
     */
    protected  $tVar = array();
    
    /**
     *模板主题 
     */
    protected $theme = null;
    
    /**
     * 模板变量赋值
     */
    public function assgin($name , $value)
    {
        if (is_array($value))
        {
            $this->tVar = array_merge($this->tVar , $value);
        }
        else 
        {
            $this->tVar[$name] = $value;
        }
    }
    
    /**
     *取得变量值 
     */
    public function getValue($name = null)
    {
        if (empty($name) || empty($this->tVar[$name]))
        {
            return $this->tVar;
        }
        else
        {            
            return $this->tVar[$name];
        }
    }
    
    /**
     *输出模板内容 
     *@param string $tempateFile 模板文件名称
     *@param string $content     模板内容
     *@param string $charset     字符编码
     *@param string $contentType 输出类型
     *@param string $suffix      缓存前缀
     *@return html
     */
    public function display($tempateFile = null, $content = null, $charset = 'UTF-8', $contentType = 'text/html', $suffix = null)
    {
        //获取系统运行状态
        getMemory('view_start_time');
        //监听视图开始标签
        Hook::listenTag('view_begin' , $tempateFile);
        //解析模板内容
        $content = $this->parseHtmlAndPHP($tempateFile,$content, $suffix);
        //输出内容
        $this->render($content, $charset, $contentType);
        // 监听结束
        Hook::listenTag('view_end');
    }
    
    /**
     * 获取当前模板主题
     * @return string 主题名称
     */
    private function getThemeName()
    {
        $theme = null;
        if (!empty($this->theme)){
           $theme = $this->theme; 
        } else {
           //是否开启自动侦测模板主题
           if (getConfig('DEFAULT_THEME')){
               //默认模板主题 接收变量
               $t = getConfig('VAR_TEMPLATE');
               if (isset($_GET[$t])){
                   $theme = $_GET[$t];
               }elseif ($mymvc_default_theme = cookie('mymvc_default_theme')){
                   $theme = $mymvc_default_theme;
               }
               if (!in_array($theme, explode(',', getConfig('THEME_LIST')) )){
                   $theme = getConfig('DEFAULT_THEME');
               }
               cookie('mymvc_default_theme' , $theme , 86400);
           }
        }
        defined('APP_THEME') or define('APP_THEME', $theme);
        
        return $theme ? $theme.'/': '';
    }
    /**
     * 获取 当前主题模板路径
     * @param  string $model 模块名称
     * @access protected
     * @return string
     */
    protected function getThemePath($file = null ,$model = __MODULE__)
    {
        if (is_file($file)) {
            return $file;
        }
        
        //获取主题名称
        $themeName = $this->getThemeName();
     
        //获取 系统模板页面分隔符
        $depar     = getConfig('TMPL_FILE_DEPR');
        
        //处理模板文件名称
        $templateFile = str_replace(':', $depar, $file);
      
        //是否跨模块调用模板文件
        if (strpos($file, '@')){
            list($model , $templateFile) = explode('@', $file);
        }
        // 定义全局变量
        if (!defined('THEME_PATH')) {
            $viewPath = getConfig('VIEW_PATH');
            define('THEME_PATH', $viewPath ? APP_PATH.$model.'/'.$templateFile :  APP_PATH.$model.'/'.getConfig('DEFAULT_V_LAYER').$templateFile );
        }
        
        //分析模板规则
       
        $controller = ($length=strrpos(__CONTROLLER__, 'Controller')) ? substr(__CONTROLLER__, 0, $length) : __CONTROLLER__;
        if (null == $templateFile){ 
            $themeFile = $controller.$depar.__ACTION__;
        } else if (false === strpos($templateFile, $depar)){
            $themeFile = $controller.$depar.$templateFile;
        }
        $thisFile = THEME_PATH.$depar.$themeFile.getConfig('TMPL_TEMPLATE_SUFFIX');
       
        $thisFile = (getConfig('TMPL_LOAD_DEFAULTTHEME') && !is_file($thisFile) && THEME_PATH != getConfig('DEFAULT_THEME')) ? 
                    dirname(THEME_PATH).'/'.getConfig('DEFAULT_THEME').'/'.$templateFile.getConfig('TMPL_TEMPLATE_SUFFIX') :  $thisFile ;
       
        return $thisFile;
    }
    /**
     * 设置当前主题
     * @param string $theme
     * @return $this
     */
    public function getTheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }
    /**
     *模板渲染输出 
     */
    private function render($content, $charset='UTF-8' , $type='text/html')
    {
        header('Content-Type:'.$type.';charset='.$charset);
        header('Cache-Control'.getConfig('HTTP_CACHE_CONTROL'));
        header('X-Powered-By:MyMVC');
        echo $content;
    }
    /**
     * 解析html模板并处理php
     * @author 王强
     * @access public
     * @param string $templateFile 模板文件名称
     * @param string $content 模板内容
     * @param string $front 模板前缀
     * @return string $content
     */
    public function parseHtmlAndPHP($templateFile = null, $content = null, $front = null)
    {
        //如果内容是空的
       
        if (empty($content)) {
            //定位模板主题
            $templateFile = $this->getThemePath($templateFile);
            !is_file($templateFile) ? getError(getLanage('_TEMPLATE_NOT_EXIST_').':'.$templateFile, 0) : null;
        }
        //页面缓存开始
        ob_start();
        ob_implicit_flush(0);
        //检测模板是否原生
        if ('php' === getConfig('TMPL_ENGINE_TYPE')){
            //处理模板变量
            extract($this->tVar, EXTR_OVERWRITE);
            empty($content) ? include_once $templateFile : eval('?>'.$content);
        } else {
            $params = array(
                'file'      => $templateFile,
                'content'   => $content,
                'perfix'    => $front,
                'var'       => $this->tVar
            );
            //调用行为解析类
            Hook::listenTag('view_parse', $params);
        }
        //获取并清空缓存
        $content = ob_get_clean();
        //模板内容检查
        Hook::listenTag('view_filter' , $content);
        return $content;
    }
}