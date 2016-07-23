<?php
namespace  Behavior;

/**
 * 缓存模板替换输出 
 * @author 王强
 */
class TemplateCacheReplaceBehavior
{
    /**
     * 入口 必须是run 
     * @param  string $content 模板内容
     * @return string
     */
    public function run(& $content)
    {
        return $this->replaceContent($content);
    }
    
    /**
     * 特殊模板变量 替换输出
     * @param string $content
     * @return string 
     */
    private function replaceContent($content)
    {
        if (empty($content))
        {
            return null;
        }
        $replaceArray = array(
          '__ROOT__'     => __ROOT__,
          '__PUBLIC__'   => __ROOT__.'/public',
          '__ACTION__'   => __ACTION__,
          '__CONTROLLER' => __CONTROLLER__,
          '__SELF__'     => _PHP_FILE_
        );
        //是否自定义了替换的
        if ($array = getConfig('CUSTOM_TEMPLATE_VAR'))
            $replaceArray = array_merge($replaceArray, $array);
        $content = str_replace(array_keys($replaceArray), array_values($replaceArray), $content);
        return $content;
    }
}