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
    protected function autoBulidVar($param) 
    {
        if ('MyMVC' === substr($param, 0, 4)) {
            
        } else if (false !== strpos($param, '.')) {
            $tempVar = explode('.', $param);
            $master  = array_shift($tempVar); // 主变量
            switch (getConfig('TMPL_VAR_IDENTIFY')) { 
                case 'array':
                    foreach ($tempVar as $key => $name) {
                        $master .= '["'.$name.'"]';
                    }
                    break;
                case 'obj' :
                    foreach ($tempVar as $key => $name) {
                        if (0 === strpos($name, '$')) {
                            $master .= '->'.$name;
                        }
                    }
                    break;
               default:$master = 'is_array('.$master.') ? '.$master[$tempVar[0]].':'.$master.'->'.$tempVar[0]; break;
            }
        } elseif (strpos($param, ':')) {
            $master = str_replace(':', '->', $param);
        } else {
            $master = $param;
        }
        return $master;
    }
    /**
     * 解析xml 标签
     */
    public function parseXmlAttr($tag, $attr, $perfix = 'wq_')
    {
        //xml过滤
        $attr = str_replace('&', '____', $attr);
        $xml = '<tpl><tag '.$attr.'/></tpl>';
        try {
            $xmlObj = new \SimpleXMLElement($xml);
            $attributes = (array)$xmlObj->tag;
            if (isset($attributes['@attributes']))
            {
                $xmlData    = array_change_key_case($attributes['@attributes']);
                if ($xmlData)
                {
                    $tag = strtolower($tag);
                    //摒弃了别名检测
                   
                    $item = $this->tags[substr($tag, strlen($perfix))];
                    //是否有必须的参数
                    $attrs = explode(',', $item['attr']);
                    $must  = array();
                    if (isset($item['must']))
                    {
                        $attrs = explode(',', $item['must']);
                    }
                    
                    foreach ($attrs as $key => $value)
                    {
                        if (isset($xmlData[$value])) 
                            $xmlData[$value] = str_replace('___', '&', $xmlData[$value]);
                        else if (false !== array_search($value, $must))
                            getError(getLanage('_PARAM_ERROR_').':'.$xmlObj[$value]);
                    }
                    return $xmlData;
                }
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
     * \w 表示匹配大小写英文字母、数字以及下划线，等价于'[A-Za-z0-9_]'
     */
    protected function parseCondition($condition)
    {
        //替换 比较字符串
        $compare = str_ireplace(array_keys($this->compare), array_values($this->compare), $condition);
        $compare = preg_replace('/\$(\w+):(\w)\s/is', '$\\1->\\2', $compare);
        switch (getConfig('TMPL_VAR_IDENTIFY')) {
            case 'array':
                $compare = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1["\\2"]', $compare);
                break;
            case 'obj':
                $compare = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1->\\2', $compare);
                break;
            default: 
                $compare = preg_replace('/\$(\w+)\.(\w+)\s/is', '(is_array($\\1)?$\\1["\\2"]:$\\1->\\2) ', $compare);
               break;
        }
        // 忽略解析特殊变量
        if(false !== strpos($condition, '$Think')){}
        return $compare;
    }
    /**
     * 获取模板标签 
     */
    public function getTags() 
    {
        return $this->tags;
    }
}