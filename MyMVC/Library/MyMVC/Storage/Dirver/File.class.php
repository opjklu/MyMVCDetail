<?php
namespace MyMVC\Storage\Dirver;
use MyMVC\Storage;

class File extends Storage
{
    /**
     *内容
     */
    private $contents;
    /**
     * 架构函数
     */
    public function __construct()
    {
    }
    /**
     *追加文件 
     */
    public function appendFile($fileName , $content ,$type="")
    {
       return  !$this->contents[$fileName]&&is_file($fileName) ? $this->putFile($fileName, $this->readFile($fileName).$content , $type) :getError("已存在该文件:".$f);
    }
    /**
     *删除文件 
     */
    public function unLink($fileName , $type="")
    {
       if (is_file($fileName)){
           unset($this->contents[$fileName]); 
           return is_file($fileName)? unlink($fileName) : false;
       }else {
           return false;
       }
    }
    /**
     *获取文件 
     */
    public function getFileName($fileName , $contenName , $type="") 
    {
        if (empty($this->contents[$fileName])){
             if (is_file($fileName))
                $this->contents[$fileName] = file_get_contents($fileName);
            else 
                return false;
        }
        $info = array(
            "mitime"  => fileatime($fileName),
            "content" =>$this->contents[$fileName]
        );
        return $info[$contenName];
    }
    /**
     *文件 读取
     */
    public function readFile($fileName , $contentName="content" , $type="")
    {
        return $this->getFileName($fileName, $contentName);
    }
    /**
     *文件写入 
     */
    public function putFile($fileName ,$contents ,$type="") 
    {
        $dir = dirname($fileName);
     
        !is_dir($dir) ? mkdir($dir , 0755 , true) : false;
        return false === file_put_contents($fileName, $contents) ? getError("写入错误:".$fileName) : $this->contents[$fileName] = $contents;
    }
    /**
     *加载文件 
     */
    public function load($fileName , $var = null, $type=null) 
    {
        is_null($var) ? false : extract($var ,EXTR_OVERWRITE);
        !$this->contents[$fileName] ? getError(getLanage('_FILE_NOT_EXITS').":".$fileName) : include $fileName;   
    }
    /**
     *是否有该文件 
     */
    public function hasFile($fileName , $type = "")
    {
        return !empty($this->contents[$fileName]) ? true : false;
    }
}