<?php
namespace MyMVC\Template;
/**
 * 内置标签解析基类 
 */
abstract class TagLib
{
    /**
     * xml 标签  文件
     */
    protected $xml = '';
    
    /**
     * 标签库 
     */
    protected $tagLib = '';
    
    /**
     * 标签定义 
     */
    protected $tags = array();
    /**
     * 标签库列表 
     */
    protected $tagLibList = array();
    
    /**
     * 标签是否有效 
     */
    protected $isTagTrue = true;
    
    /**
     * 当前模板对象 
     */
    protected $tempObj = null;
    /**
     * 要解析的标签库列表 
     */
    protected $parseTag = array();
    
    /**
     * 比较运算 
     */
    protected $compare = array(
        'neq'  => '!=',
        'nheq' => '!==',
        'eq'   => '==',
        'lt'   => '<',
        'gt'   => '>',
        'egt'  => '>=',
        'elt'  => '<=',
    );
    
    /**
     * 架构 函数 初始化 
     */
    public function __construct()
    {
        $this->tagLib = substr(get_class($this), 6);
        $this->tempObj = \MyMVC\MyMVC::getIntrance('MyMVC\\Template');
    }
    
    /**
     * 处理模板变量 
     */
    
    /**
     * 解析xml 标签
     */
    public function parseXmlAttr($tag, $attr)
    {
        //xml过滤
        $attr = str_replace('&', '____', $attr);
        $xml = '<tpl><tag '.$attr.'/></tpl>';
        try {
            $xmlObj = new \SimpleXMLElement($xml);
            $attributes = (array)$xmlObj->tag;
            if (isset($attributes['attributes']))
            {
                $xmlData    = array_change_key_case($attributes['@attributes']);
                
            }
            else 
            {
                return array();
            }
           
        } catch (\MyMVC\Error $e) {
             getError(getLanage('_XML_TAG_ERROR_').':'.$xml);
        }
    }
    /**
     * 解析特殊模板变量 
     */
    
    /**
     * 解析条件表达式 
     */
    
    /**
     * 获取模板标签 
     */
    public function getTags() 
    {
        return $this->tags;
    }
}