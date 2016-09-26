<?php
namespace MyMVC;
/**
 * 模型类 
 * @author 王强
 */
 
class Model 
{
    // 操作状态
    const MODEL_INSERT          =   1;      //  插入模型数据
    const MODEL_UPDATE          =   2;      //  更新模型数据
    const MODEL_BOTH            =   3;      //  包含上面两种方式
    const MUST_VALIDATE         =   1;      // 必须验证
    const EXISTS_VALIDATE       =   0;      // 表单存在字段则验证
    const VALUE_VALIDATE        =   2;      // 表单值不为空则验证
    
    // 当前数据库操作对象
    protected $db               =   null;
    // 主键名称
    protected $pk               =   'id';
    // 主键是否自动增长
    protected $autoinc          =   false;
    // 数据表前缀
    protected $tablePrefix      =   null;
    // 模型名称
    protected $name             =   '';
    // 数据库名称
    protected $dbName           =   '';
    //数据库配置
    protected $connection       =   '';
    // 数据表名（不包含表前缀）
    protected $tableName        =   '';
    // 实际数据表名（包含表前缀）
    protected $trueTableName    =   '';
    // 最近错误信息
    protected $error            =   '';
    // 字段信息
    protected $fields           =   array();
    // 数据信息
    protected $data             =   array();
    // 查询表达式参数
    protected $options          =   array();
    protected $_validate        =   array();  // 自动验证定义
    protected $_auto            =   array();  // 自动完成定义
    protected $_map             =   array();  // 字段映射定义
    protected $_scope           =   array();  // 命名范围定义
    // 是否自动检测数据表字段信息
    protected $autoCheckFields  =   true;
    // 是否批处理验证
    protected $patchValidate    =   false;
    // 链操作方法列表
    protected $methods          =   array('order','alias','having','group','lock','distinct','auto','filter','validate','result','token');
    
    /**
     * 架构函数 
     */
    public function __construct($name= '', $tableNamePerfix = '', $connection = '')
    {
        //模型初始化
        $this->init();
        if (!empty($name)) {
            strpos($name, '.') ? list($this->dbName, $this->tableName) = explode('.', $name) : $this->tableName = $name;
        } else {
            $name = $this->getModelName();
        }
        //设置前缀
        $this->tablePrefix = $tableNamePerfix === '' ? (isset($this->tablePrefix)? : getConfig('DB_PERFIX')) : $tableNamePerfix;
        
        //初始化连接
        $this->dbConnect(0, !empty($this->connection) ? $this->connection : $connection, true);
    }
    /**
     * 初始化连接 
     * @param int $linkNum            连接序号
     * @param array or string $config 数据库配置信息
     * @param bool $focre             是否强制重新连接
     * @return DataBase\Driver
     */
    public function dbConnect($linkNum, $config = '', $focre = false)
    {
        static $link = array();
        if (empty($link[$linkNum]) || $focre) {
            // 创建一个新的实例
            if(!empty($config) && is_string($config) && false === strpos($config,'/')) { // 支持读取配置参数
                $config  =  getConfig($config);
            }
            $link[$linkNum] = DataBase::getInvaition($config);
        } else if (null === $config) {
            //关闭连接
            $link[$linkNum]->close();
            unset($link[$linkNum]);
            return '';
        }
        $this->db = $link[$linkNum];
        //检测字段
        !empty($this->fields) ? : $this->getFileds();
        return $this;
    }
    /**
     * 获取完整表名
     */
    public function getAllTableName()
    {
        if (empty($this->trueTableName)){
            $tablName  = empty($this->tablePrefix) ? null : $this->tablePrefix;
            $tablName .= !empty($this->tableName) ? $this->tableName : str_replace('\\', '', strrchr($this->name, '\\'));
            $this->trueTableName = strtolower($tablName);
        }
        return (!empty($this->dbName) ? $this->dbName.'.':''). $this->trueTableName;
    }
    /**
     * 获取 当前操作对象 
     */
    protected function getModelName($suffix = 'Model')
    {
        if (empty($this->name)) {
            $name = substr(get_class($this), 0, -strlen($suffix));
            $this->name = ($length = strpos($name, '\\')) ? substr($name, $length+1) :$name;
        }
        return $this->name;
    }
    //初始化
    public function init(){}
    /**
     * 获取所有字段 
     */
    public function getFileds($dbName =null)
    {
        $this->db->setModel($this->name);
        $fields     = $this->db->getColum($this->getAllTableName(), $dbName);
        $dataArray  = array();
        if (!empty($fields))
        {
            foreach ($fields as $key => $value)
            {
               $dataArray[$value['COLUMN_NAME']] = preg_replace('/\(.*\)/', '', $value['COLUMN_TYPE']).(empty($value['COLUMN_KEY']) ? null: ','.$value['COLUMN_KEY']);
            }
        }
        
        $this->fields = !empty($dataArray) ? $dataArray : null;
        
        //缓存操作
        return $this->fields;
    }
    /**
     * 插入数据
     */
    public function insert(array $data, $options = array(), $replace = false)
    {
        if (empty($data)) {
            if (!empty($this->data)) {
                $data = $this->data;
                //重置数据
                $this->data = array();
            } else {
                $this->error = getLanage('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        //分析数据
        $options = $this->parseCondition($options);
        //数据处理
        $data = $this->parseData($data);
        if (false === $this->beforeInsert($data, $options)) {
            return false;
        }
        //插入数据库
        $insertId = $this->db->insert($data, $options, $replace);
    }
    // 插入数据前的回调方法
    protected function beforeInsert(&$data,$options) {}
    // 插入成功后的回调方法
    protected function afterInsert($data,$options) {}
    /**
     * 数据处理 
     * @param mixed $data 要处理的数据
     * @return mixed；
     */
    protected function parseData(array $data = array())
    {
        if (!empty($this->fields))
        {
            if (!empty($this->options['fields'])) {
                $fields = $this->options['fields'];
                unset($this->options['fields']);
                if (!is_array($fields)) getError('只能为数组'.':'.'$fields');
            } else {
                $fields = $this->fields;
            }
            // 类型检测
            $this->parseDataType($data, $fields);
        }
        //安全过滤
        if (isset($this->options['filter'])) {
            $data = array_map($this->options['filter'], $data);
            unset($this->options['filter']);
        }
        $this->beforeWrite($data);
        return $data;
    }
    // 写入数据前的回调方法 包括新增和更新
    protected function beforeWrite(&$data) {}
    /**
     * 分析表达式 
     * @param mixed $options
     * @access protected
     * @return mixed;
     */
    protected function parseCondition($options = array())
    {
        $options = is_array($options) ? array_merge($this->options, $options) : $options;
        
        //自动获取表名
        if (!isset($options['table'])) {
            $options['table'] = $this->getAllTableName();
            $fields = $this->fields;
        } else {
            $fields = $this->getFileds();
        } 
        //清空options 以免影响 sql 组装
        $this->options = array();
        
        // 数据表别名
        if(!empty($options['alias'])) {
            $options['table']  .=   ' '.$options['alias'];
        }
        // 记录操作的模型名称
        $options['model']       =   $this->name;
        
        // 字段类型验证
        if(isset($options['where']) && is_array($options['where']) && !empty($fields) && !isset($options['join'])) {
            // 对数组查询条件进行字段类型检查
            $this->parseDataType($options['where']);
        }
        
        // 表达式过滤
        $this->optionsFilter($options);
        return $options;
    }
    protected function optionsFilter($options = array()){}
    
    /**
     * 更新数据库操作
     */
    public function update(array $data, $options = array())
    {
        
    }
    /**
     * 数据类型检测 
     * @param array  $data      要检测得数据
     * @access protected
     * @return null or array
     */
    protected function parseDataType(array &$data, array $fields = null,$perfix = ':')
    {
        if (empty($data))
            return null;
        $fields = empty($fields) ? $this->fields : $fields;
        foreach ($data as $key => &$value)
        {
            if (array_key_exists($key,$fields) || (0 === strpos($key, $perfix) && array_key_exists(substr($key, 1), $fields)))
            {
                switch ($flag = $fields[$key])
                {
                    case false !== strpos($flag, 'int'):
                        $value = intval($value);
                    break;
                    case 'varchar':
                        $value = (string)$value;
                    break;
                    case 'decimal':
                        $value = floatval($value);
                    break;
                    case 'bool':
                        $value = (bool)$value;
                    break;
                }
            }
            else 
            {
              unset($data[$key]);   
            }
        }
    }
    /**
     * @param array $array
     * @param array $not_key
     * @param string $is_check_number
     * @param string $is_validate_token
     * @return NULL|unknown
     * */
    
    public function create(array $array = null, array $not_key = null, $is_check_number = FALSE, $is_validate_token = FALSE)
    {
        $data = empty($array) ? $_POST : $array;
         
        //是否验证表单令牌
        if ($is_validate_token === true && ($validate = getConfig('is_validate_token')))
        {
            if (empty($data['validate']) || $data['validate'] !== $validate)
                return null;
        }
        //检测数据
//         if(!isCheckData($data, $not_key, $is_check_number)) return null;
         
        //判断是更新还是插入 的标记变量
        $flag = 0;
        //对比字段
        foreach ($data as $key => $value)
        {
            if (!in_array($key, $this->db->fields))
            {
                unset($data[$key]);
            }
            //判断是更新还是插入
            if ($this->db->primary === $key)
            {
                $flag = 1;
            }
        }
        if ($flag === 0 && !empty($data[$this->db->primary]))//插入
        {
            unset($data[$this->primary]);
        }
        //字段类型验证
         
        //首先获取所有字段类型
        $fields_type = $this->query('select COLUMN_TYPE,COLUMN_NAME from information_schema.COLUMNS where table_name = "'.$this->$tableName.'" and table_schema = "'.$this->dbName.'"');
        if (!empty($fields_type))
        {
            foreach ($fields_type as $key => $value)
            {
                if (!array_key_exists($value['COLUMN_NAME'], $data))
                {
                    unset($fields_type[$key]['COLUMN_NAME']);
                    unset($fields_type[$key]['COLUMN_TYPE']);
                }
                elseif (0 === strpos($value['COLUMN_TYPE'], 'int') || strpos($value['COLUMN_TYPE'], 'int'))
                {
                    $data_colum[$value['COLUMN_NAME']] = 'integer';
                }
                else if (0 === strpos($value['COLUMN_TYPE'], 'varchar') || strpos($value['COLUMN_TYPE'], 'date') || 0 === strpos($value['COLUMN_TYPE'], 'text')) //.....以后在完善
                {
                    $data_colum[$value['COLUMN_NAME']] = 'string';
                }
                else
                {
        	           $data_colum[$value['COLUMN_NAME']] = $value['COLUMN_TYPE'];
                }
            }
            if (empty($data_colum))
            {
                return null;
            }
            //比较类型
            foreach ($data_colum as $type_key => $type_value)
            {
                if (gettype($data[$type_key]) != $type_value)
                {
                    $data[$type_key] = eval('('.$type_value.')$data[$type_key];');
                }
            }
            return $data;
        }
        else
        {
            return null;
        }
    }
    /**
     *获取全部子集分类
     *@param integer $video_id 视频分类编号
     */
    public function get_children($video_id ,$tabale = 'video/term_taxonomy_model' , $key ="parent_id")
    {
        //         ini_set(‘memory_limit’,’288M’);
        // 根据地区编号  查询  该地区的所有信息
        $term_taxonomy_model = RC_Model::model($tabale);
        $video_data   = $term_taxonomy_model->field('taxonomy_id')->where('parent_id="'.$video_id.'" and object_app="ecjia.video" and is_show=1')->select();
    
        foreach ($video_data as $key => &$value)
        {
            if(!empty($value['taxonomy_id']))
            {
                $data .= ','. $value['taxonomy_id'];
                $child = self::get_children($value['taxonomy_id']  , $tabale , $key);
                if (!empty($child))
                {
                    foreach ($child as $key_value => $value_key)
                    {
                        if (!empty($value_key['taxonomy_id']))
                        {
                            $data.=','.$value_key['taxonomy_id'];
                        }
                    }
                }
                unset($value);
                unset($child);
            }
        }
        return !empty($data) ? substr($data , 1) : null;
    }
}