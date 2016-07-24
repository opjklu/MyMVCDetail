<?php
namespace MyMVC\Template\TagLib;
use MyMVC\Template\TagLib;
defined('MyMVC_PATH') or die("NO Resocure");
/**
 * 标签解析类
 * @author 王强 
 */
class Wq extends TagLib
{
    /**
     * 模板标签定义 
     * @param string attr 标签属性
     * @param int leval 嵌套层次
     * @param int close 是否闭合
     */
    protected $tags = array(
        'php'     => array('level' => 1, 'attr' => '', 'close' => 1),
        'foreach' => array('level' => 3, 'attr' => 'from,key,item', 'close' => 1),
        'volist'  => array('level' => 3, 'attr' => 'from,key,item', 'close' => 1),
        'if'      => array('level' => 3, 'attr' => 'condition', 'close' => 1),
        'else'    => array('level' => 3, 'attr' => 'condition', 'close' => 1),
        'elseif'  => array('level' => 3, 'attr' => 'condition', 'close' => 0),
        'empty'   => array('level' => 3, 'attr' => 'condition', 'close' => 1),
        'for'     => array('level' => 3, 'attr' => 'start,end,name', 'close' => 1),
    );
    
    /**
     * 解析php标签 
     */
    public function wq_php($tag, $content) 
    {
        $parStr = '<?php '.$content.' ?>';
        return $parStr;
    }
}