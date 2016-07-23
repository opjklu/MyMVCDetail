<?php
namespace Behavior;

use MyMVC\Storage;
/**
 * 写入静态缓存
 * @author 王强 
 */
class WriterStaticHTMLCacheBehavior
{
    /**
     * 行为的执行入口必须是run 
     */
    public function run(&$content) 
    {
        if (getConfig('HTML_CACHE_ON') || defined('HTML_CACHE_FILE'))
        {
            Storage::putFile(HTML_CACHE_FILE, $content, 'html');
        }
    }
}