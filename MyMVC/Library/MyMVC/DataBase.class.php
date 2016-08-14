<?php
namespace  MyMVC;

/**
 * 数据库 抽象类 
 */
class DataBase
{
    // 数据库表达式
    protected $comparison = array(
        'eq' =>'=',
        'neq'=>'<>',
        'gt' =>'>',
        'egt'=>'>=',
        'lt' =>'<',
        'elt'=>'<=',
    );
    // 查询表达式
    protected $selectSql  = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%COMMENT%';
    // 参数绑定
    protected $bind       = array();
    //数据库配置
    protected $config     = array();
    //表名
    protected $tableName = '';
    //表前缀
    protected $tablePerfix = '';
    //返回几行
    protected $numRows     = 0;
    // sql
    protected $sql         = NULL;
    // 当前查询ID
    protected $queryID    = null;
    //当前连接
    protected $link        = null;
    //数据库类型
    protected $dbType      = '';
    //数据库所有连接
    protected static $linkAll     = array();
    //最后插入的
    protected $lastInsertId = 0;
    //当前操作模型
    protected $model = NULL;
    
    //操作数据
    protected static $dataArray = array();
    
    private function __construct($config = '')
    {
        $guid = create_guid();
        
        if (!isset(self::$linkAll[$guid]))
        {
            self::$linkAll[$guid] = $this->factory($config);
            $this->link =  self::$linkAll[$guid];
        }
        return self::$linkAll[$guid];
    }
    /**
     * 取得类得实例 
     */
    public static function getInvaition($config = '')
    {
        static $inition;
        if (empty($inition))
        {
            $inition =new self($config);
        }
        return $inition;
    }
    /**
     * 取得数据库的实例连接对象
     */
    protected function factory($config = '')
    {
        static $dbObj = null;
        if ($dbObj === null)
        {
            //分析配置
            $parseConfig = $this->parseConfig($config);
            if (!$parseConfig){getError(getLanage('_NO_DB_CONFIG_'));}
            
            if (strpos($parseConfig['dbms'], '\\')) {
                $obj = $parseConfig['dbms'];
            } else {
                $obj = 'MyMVC\\DataBase\\Driver\\'.ucwords(strtolower($parseConfig['dbms']));
            }
            if (class_exists($obj)) {
                $dbObj = new $obj($config);
                return $dbObj;
            } else {
                getError(getLanage('_NOT_LOAD_DB_').':'.$parseConfig['dbms']);
            }
        }
        else 
        {
             return $dbObj;
        }
        
    }
    /**
     * 分析数据库配置信息，支持数组和DSN
     * @access private
     * @param mixed $db_config 数据库配置信息
     * @return string
     */
    private function parseConfig($db_config='') {
        if ( !empty($db_config) && is_string($db_config)) {
            // 如果DSN字符串则进行解析
            $db_config = $this->parseDSN($db_config);
        }elseif(is_array($db_config)) { // 数组配置
            $db_config = array_change_key_case($db_config);
            $config    = array('db_type', 'db_user', 'db_pwd', 'db_host', 'db_name', 'db_dsn', 'db_params', 'db_charset'); 
            foreach ($config as $value)
            {
                if (!array_key_exists($value, $db_config))
                {
                    getError('请按照此数组依次配置'.':'.$config);
                }
            }
            $db_config = array(
                'dbms'      =>  $db_config['db_type'],
                'username'  =>  $db_config['db_user'],
                'password'  =>  $db_config['db_pwd'],
                'hostname'  =>  $db_config['db_host'],
                'hostport'  =>  $db_config['db_port'],
                'database'  =>  $db_config['db_name'],
                'dsn'       =>  $db_config['db_dsn'],
                'params'    =>  $db_config['db_params'],
                'charset'   =>  $db_config['db_charset'],
            );
        }elseif(empty($db_config)) {
            // 如果配置为空，读取配置文件设置
            if( getConfig('DB_DSN') && 'pdo' != strtolower(getConfig('DB_TYPE')) ) { // 如果设置了DB_DSN 则优先
                $db_config =  $this->parseDSN(getConfig('DB_DSN'));
            }else{
                $db_config = array (
                    'dbms'      =>  getConfig('DB_TYPE'),
                    'username'  =>  getConfig('DB_USER'),
                    'password'  =>  getConfig('DB_PWD'),
                    'hostname'  =>  getConfig('DB_HOST'),
                    'hostport'  =>  getConfig('DB_PORT'),
                    'database'  =>  getConfig('DB_NAME'),
                    'dsn'       =>  getConfig('DB_DSN'),
                    'params'    =>  getConfig('DB_PARAMS'),
                    'charset'   =>  getConfig('DB_CHARSET'),
                );
            }
        }
        $this->config = $db_config;
        return $db_config;
    }
    
    /**
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName#charset
     * @static
     * @access public
     * @param string $dsnStr
     * @return array
     */
    public function parseDSN($dsnStr) {
        if( empty($dsnStr) ){return false;}
        $info = parse_url($dsnStr);
        if($info['scheme']){
            $dsn = array(
                'dbms'      =>  $info['scheme'],
                'username'  =>  isset($info['user']) ? $info['user'] : '',
                'password'  =>  isset($info['pass']) ? $info['pass'] : '',
                'hostname'  =>  isset($info['host']) ? $info['host'] : '',
                'hostport'  =>  isset($info['port']) ? $info['port'] : '',
                'database'  =>  isset($info['path']) ? substr($info['path'],1) : '',
                'charset'   =>  isset($info['fragment'])?$info['fragment']:'utf8',
            );
        }else {
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/',trim($dsnStr),$matches);
            $dsn = array (
                'dbms'      =>  $matches[1],
                'username'  =>  $matches[2],
                'password'  =>  $matches[3],
                'hostname'  =>  $matches[4],
                'hostport'  =>  $matches[5],
                'database'  =>  $matches[6]
            );
        }
        $dsn['dsn'] =  ''; // 兼容配置信息数组
        return $dsn;
    }
    
    /**
     * 初始化数据库连接
     * @access protected
     * @param boolean $master 主服务器
     * @return void
     */
    protected function initConnect($master=true) {
        if(1 == getConfig('DB_DEPLOY_TYPE'))
            // 采用分布式数据库
            $this->_linkID = $this->multiConnect($master);
        else
            // 默认单数据库
            if ( !$this->connected ) $this->_linkID = $this->connect();
    }
    
    /**
     * 连接分布式服务器
     * @access protected
     * @param boolean $master 主服务器
     * @return void
     */
    protected function multiConnect($master=false) {
        static $_config = array();
        if(empty($_config)) {
            // 缓存分布式数据库配置解析
            foreach ($this->config as $key=>$val){
                $_config[$key]      =   explode(',',$val);
            }
        }
        // 数据库读写是否分离
        if(getConfig('DB_RW_SEPARATE')){
            // 主从式采用读写分离
            if($master)
                // 主服务器写入
                $r  =   floor(mt_rand(0,getConfig('DB_MASTER_NUM')-1));
            else{
                if(is_numeric(getConfig('DB_SLAVE_NO'))) {// 指定服务器读
                    $r = getConfig('DB_SLAVE_NO');
                }else{
                    // 读操作连接从服务器
                    $r = floor(mt_rand(getConfig('DB_MASTER_NUM'),count($_config['hostname'])-1));   // 每次随机连接的数据库
                }
            }
        }else{
            // 读写操作不区分服务器
            $r = floor(mt_rand(0,count($_config['hostname'])-1));   // 每次随机连接的数据库
        }
        $db_config = array(
            'username'  =>  isset($_config['username'][$r])?$_config['username'][$r]:$_config['username'][0],
            'password'  =>  isset($_config['password'][$r])?$_config['password'][$r]:$_config['password'][0],
            'hostname'  =>  isset($_config['hostname'][$r])?$_config['hostname'][$r]:$_config['hostname'][0],
            'hostport'  =>  isset($_config['hostport'][$r])?$_config['hostport'][$r]:$_config['hostport'][0],
            'database'  =>  isset($_config['database'][$r])?$_config['database'][$r]:$_config['database'][0],
            'dsn'       =>  isset($_config['dsn'][$r])?$_config['dsn'][$r]:$_config['dsn'][0],
            'params'    =>  isset($_config['params'][$r])?$_config['params'][$r]:$_config['params'][0],
        );
        return $this->connect($db_config,$r);
    }
    /**
     * 关闭连接 
     */
    public function close(){}
    
    /**
     * 析构方法
     * @access public
     */
    public function __destruct() {
        // 释放查询
        if ($this->queryID){
//             $this->free();
        }
        // 关闭连接
        $this->close();
    }
    
    /**
     * 获取表的所有字段 
     */
    public function getColum($table = null, $dbName = null, $perfix = 'Model')
    {
        $table  = $table  !== null ? $table  :  $this->model ;
        $dbName = $dbName !== null ? $dbName : getConfig('DB_NAME');
        if (!empty(self::$dataArray[$dbName][$table]))
        {
            return self::$dataArray[$dbName][$table];
        }
        else 
        {
            $link = $this->connect($this->config);
            switch  ($obj = get_class($link))
            {
                case 'mysqli':
                    $sql = 'select COLUMN_NAME,COLUMN_TYPE,COLUMN_KEY from information_schema.COLUMNS where table_name = ? and table_schema = ?;';
                    break;
                case 'pdo':
                    $sql = 'select COLUMN_NAME from information_schema.COLUMNS where table_name = :table_name and table_schema = :table_schema;';
                    break;
            }
            $data = $link->prepare($sql);
            $i=0;
            $dataArray = array();
            switch  ($obj)
            {
                case 'mysqli':
                    if ( $data->bind_param('ss', $table, $dbName) && $data->execute())
                    {
                        $data->store_result();
                    
                        $variables = array();
                        $meta = $data->result_metadata();
                    
                        // 从这里开始就迷糊
                        $variables = array();
                        while($field = $meta->fetch_field())
                            $variables[] = &$dataArray[$field->name]; // pass by reference
                        call_user_func_array(array($data, 'bind_result'), $variables);
                        while($data->fetch())
                        {
                            $array[$i] = array();
                            foreach($dataArray as $k=>$v)
                                $array[$i][$k] = $v;
                            $i++;
                        }
                        self::$dataArray[$dbName][$table] = $array;
                        return $array;
                    }
                    else
                    {
                        getError('查询出错了'.':'.$sql);
                    }
                    break;
                case 'pdo':
                    $data->execute(array(':table_name' => $table, ':table_schema' => $dbName));
                    
                    while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
                    }
                    break;
            }
        }
        return null;
    }
    /**
     * 插入数据 
     * @param array $data 要插入的数据
     * @param mixed $options 条件
     * @param bool  $replace 是否替换 数据
     * @return bool | int
     */
    public function insert(array $data, $options = array(), $replace = false)
    {
        
    }
    /**
     * 调用子类方法 
     */
    public  function __call($method , $args) 
    {
        return  method_exists($this->link, $method) ? call_user_func_array(array($this->link , $method), $args) :getError(getLanage('_METHOD_NOT_EXIST_').':'.$method);
    }
    
    public function setModel($name)
    {
        $this->model = $name;
    }
}