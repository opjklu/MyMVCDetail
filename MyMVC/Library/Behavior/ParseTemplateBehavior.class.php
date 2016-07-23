<?php
namespace Behavior;
use MyMVC\Storage;
use MyMVC;

/**
 * 模板解析行为类
 * @author 王强
 */
class ParseTemplateBehavior
{
    /**
     *入口 必须是run 
     */
    public function run(&$param) 
    {
        $engine       = strtolower(getConfig('TMPL_ENGINE_TYPE')); //模板引擎
        $templateFile = empty($param['file'])   ? $param['content'] : $param['file'];
      
        $perfix       = empty($param['perfix']) ? $param['perfix']  : getConfig('TMPL_CACHE_PREFIX');
        if ('mymvc' == $engine) {
            //判断缓存是否有效
            if (!empty($param['content']) && ($this->checkCache($param['file'],$perfix) || $this->checkCacheContent($param['content'], $perfix)) ){
                Storage::load(getConfig('APP_CACHE_PATH').$perfix.md5($templateFile).getConfig('TMPL_CACHFILE_SUFFIX') , $param['var']);
            } else {
                //编译文件
                $templateObj = MyMVC\MyMVC::getIntrance('MyMVC\\Template');
               
                $parseFile   = $templateObj->parseTemplateFile($templateFile , $param['var'] , $perfix);
            }
        } else {
            $class = strpos($engine, '\\') ? $engine : 'Template\\Dirver\\'.$engine;
            class_exists($class) ? null : getError(getLanage('_CLASS_NOT_EXIST_').':'.$class);
            $obj = new $class();
            $parseFile = $obj->parseTemplateFile($templateFile , $param['var'] , $perfix);
        }
    }
    
    /**
     *检查缓存文件是否有效
     *@param string $templateFile 缓存文件名
     *@author 王强
     *@return bool
     */
    public function checkCache($templateFile, $perfix = null)
    {
        //优先检查配置
        if (!getConfig('TMPL_CACHE_ON'))
        {
            return  false;
        }
        //缓存文件
        $cacheFile = getConfig('APP_CACHE_PATH').$perfix.md5($templateFile).getConfig('TMPL_CACHFILE_SUFFIX');
        //检测是否有这个文件
        if (!Storage::hasFile($cacheFile)){
            return false;
        } elseif (fileatime($cacheFile) > Storage::getFileName($cacheFile , 'mitime')) { //模板缓存是否有更新
            return false;
        } elseif (getConfig('TMPL_CACHE_TIME') !=0 && time() > Storage::getFileName($cacheFile , 'mitime')+getConfig('TMPL_CACHE_TIME')) {//缓存期是否有效
            return false;
        }
        //是否开启布局
        if (getConfig('LAYOUT_ON')) {
            $cacheFile = THEME_PATH.getConfig('LAYOUT_NAME').getConfig('TMPL_TEMPLATE_SUFFIX');
            if (Storage::hasFile($cacheFile) && fileatime($cacheFile) > Storage::getFileName($cacheFile, 'mitime')){
                return false;
            }
        }
        return true;
    }
    /**
     *检查缓存内容是否有效 
     *@param string $content 缓存内容
     *@param string $perfix  缓存前缀
     *@access public
     *@author 王强
     *@return bool
     */
    protected function checkCacheContent($content, $perfix=null)
    {
       
        return  Storage::hasFile(getConfig('APP_CACHE_PATH').$perfix.md5($content).getConfig('TMPL_CACHFILE_SUFFIX')) ? true : false;
        
    }
}