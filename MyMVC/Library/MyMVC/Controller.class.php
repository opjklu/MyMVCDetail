<?php
namespace MyMVC;
/**
 *控制器抽象类 
 */
abstract class Controller
{
    /**
     *视图 
     */
    protected $view = null;
    
    /**
     *控制器参数 
     */
    protected $config = array();
    
    /**
     *架构函数 
     */
    public function __construct($args = null)
    {
        //监听开始
        Hook::listenTag('action_begin' , $this->config);
        //取得视图对象
        $this->view = MyMVC::getIntrance("MyMVC\\View");
        
        if (method_exists($this, 'init'))
        {
            $this->init($args);
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
    public function display($tempateFile = null, $content=null, $charset = 'UTF-8', $contentType = 'text/html', $suffix = null)
    {
        return $this->view->display($tempateFile, $content, $charset, $contentType, $suffix);
    }
    
    /**
     *输出自定义内容
     *@param string $content     模板内容
     *@param string $charset     字符编码
     *@param string $contentType 输出类型
     *@param string $suffix      缓存前缀
     *@return html
     */
    public function userShowContent($content=null, $charset = 'UTF-8', $contentType = 'text/html', $suffix = null)
    {
        return $this->view->display('', $content, $charset, $contentType, $suffix);
    }
    /**
     *魔术方法
     *@param string $methods 方法名
     *@param string | array $args 参数 
     */
    public function  __call($methods , $args)
    {
        //比较方法名是否相等
       
        if (0 === strcasecmp($methods, __ACTION__.getConfig('ACTION_SUFFIX')) && method_exists($this, '_empty'))
        {
            $this->_empty($args);
            //是否有默认模板
            if (file_exists($this->view->parseTemplate()))
            {
                $this->display();
            }
            else
            {
                getError(getLanage('_ERROR_ACTION_').':'.__ACTION__);
            }
        }
        else 
        {
            getError(__CLASS__.':'.$methods.getLanage('_METHOD_NOT_EXIST_'));
            return ;
        }
        
    }
    /**
     *析构 
     */
    public function __destruct()
    {
        unset($this);
    }
}