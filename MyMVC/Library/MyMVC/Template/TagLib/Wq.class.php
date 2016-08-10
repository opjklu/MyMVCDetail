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
        'volist'  => array('level' => 3, 'attr' => 'name,id,offset,length,key,mod', 'close' => 1),
        'if'      => array('level' => 3, 'attr' => 'condition', 'close' => 1),
        'else'    => array('level' => 3, 'attr' => '', 'close' => 0),
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
    
    /**
     * 解析 volist 
     */
    public function wq_volist($tag, $content)
    {
        //设置默认值
        isSetDefaultValue($tag, array('mod'), 2);
        isSetDefaultValue($tag, array('key', 'empty'), 'key');
        $name = $tag['name'];
        $key  = $tag['key'];
        $id   = $tag['id'];
        $mod  = $tag['mod'];
        
        $empty = $tag['empty'];
        
        //支持调用函数 <volist name=':fun($args)' key=key id='value'></volist>
        $parseStr = '<?php ';
        if (0 === strpos($name, ':')) {
            $parseStr .= '$_result='.substr($name, '1').';'; 
            $name      = '$_result';
        } else {
            $name = $this->autoBulidVar($name);
        }
        $parseStr .= 'if(is_array('.$name.')'.'):'.'$'.$key.'=0;';
        if (isset($tag['length']) && $tag['length'] != '') {
            $parseStr .= '$_list = array_slice('.$name.', '.$tag['offset'].', '.$tag['length'].', true)'.';';
        } elseif (isset($tag['offset']) && $tag['offset'] != '') {
            $parseStr .= '$_list = array_slice('.$name.', '.$tag['offset'].', null, true)'.';';
        } else {
            $parseStr .='$_list = '.$name.';';
        }
        $parseStr .= 'if(count($_list) == 0): echo "'.$empty.'";';
        $parseStr .= 'else : foreach($_list as $key => $'.$id.') :';
        $parseStr .= '$mod = ($'.$key.'%'.$mod.');';
        $parseStr .= '++$'.$key.';?>';
        $parseStr .= $this->tempObj->parse($content);//解析内容
        $parseStr .= '<?php endforeach; endif; else: echo "'.$empty.'"; endif; ?>';
        
        if (!empty($parseStr)) {
            return $parseStr;
        } else {
            return null;
        }
    }

    /**
     * if 
     */
    public function wq_if($tag, $content) 
    {
        $codition = $this->parseCondition($tag['condition']);
       
        $parseStr = '<?php if('.$codition.') :?>'.$content.'<?php endif;?>';
        return $parseStr;
    }
    
    public function wq_elseif($tag, $content)
    {
        $codition = $this->parseCondition($tag['condition']);
         
        $parseStr = '<?php elseif('.$codition.') :?>'.$content;
        return $parseStr;
    }
    
    public function wq_else($tag)
    {
        $parseStr = '<?php else :?>';
        return $parseStr;
    }
}