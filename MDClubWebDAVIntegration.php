<?php
/**
 * MDClub与WebDAV集成类
 * 
 * 此类用于将MDClub的数据库操作重定向到WebDAV存储
 * 它通过覆盖MDClub的数据库连接类来实现
 */

require_once __DIR__ . '/WebDAVAdapter.php';

class MDClubWebDAVIntegration {
    private static $instance;
    private $webdavAdapter;
    private $modelMappings = [];
    
    /**
     * 构造函数
     */
    private function __construct() {
        $this->webdavAdapter = new WebDAVAdapter();
        $this->initModelMappings();
    }
    
    /**
     * 获取单例实例
     * 
     * @return MDClubWebDAVIntegration
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初始化模型映射
     */
    private function initModelMappings() {
        // 定义MDClub模型与WebDAV存储的映射关系
        $this->modelMappings = [
            'user' => [
                'table' => 'users',
                'primaryKey' => 'user_id',
                'fields' => [
                    'user_id', 'username', 'email', 'password', 'avatar', 'cover', 
                    'create_time', 'update_time', 'last_login_time', 'status'
                ]
            ],
            'topic' => [
                'table' => 'topics',
                'primaryKey' => 'topic_id',
                'fields' => [
                    'topic_id', 'name', 'description', 'cover', 'create_time', 
                    'update_time', 'delete_time', 'follow_count', 'article_count', 'question_count'
                ]
            ],
            'article' => [
                'table' => 'articles',
                'primaryKey' => 'article_id',
                'fields' => [
                    'article_id', 'user_id', 'title', 'content', 'create_time', 
                    'update_time', 'delete_time', 'comment_count', 'view_count', 'vote_count'
                ]
            ],
            'question' => [
                'table' => 'questions',
                'primaryKey' => 'question_id',
                'fields' => [
                    'question_id', 'user_id', 'title', 'content', 'create_time', 
                    'update_time', 'delete_time', 'comment_count', 'view_count', 'vote_count'
                ]
            ],
            'answer' => [
                'table' => 'answers',
                'primaryKey' => 'answer_id',
                'fields' => [
                    'answer_id', 'question_id', 'user_id', 'content', 'create_time', 
                    'update_time', 'delete_time', 'comment_count', 'vote_count'
                ]
            ],
            'comment' => [
                'table' => 'comments',
                'primaryKey' => 'comment_id',
                'fields' => [
                    'comment_id', 'relation_type', 'relation_id', 'user_id', 
                    'content', 'create_time', 'delete_time', 'vote_count'
                ]
            ],
        ];
    }
    
    /**
     * 安装集成
     * 
     * 此方法将修改MDClub的数据库配置，使其使用WebDAV存储
     */
    public function install() {
        // 创建WebDAV存储目录结构
        $this->createStorageStructure();
        
        // 修改MDClub配置
        $this->modifyMDClubConfig();
        
        // 创建必要的索引文件
        $this->createIndexFiles();
        
        return true;
    }
    
    /**
     * 创建WebDAV存储目录结构
     */
    private function createStorageStructure() {
        // 这里假设WebDAV适配器已经在构造函数中创建了基本目录结构
        // 如果需要创建额外的目录，可以在这里添加
    }
    
    /**
     * 修改MDClub配置
     */
    private function modifyMDClubConfig() {
        // 读取MDClub配置文件
        $configPath = __DIR__ . '/mdclub-v1/config/config.php';
        
        if (!file_exists($configPath)) {
            // 如果配置文件不存在，创建一个新的
            $this->createMDClubConfig($configPath);
            return;
        }
        
        // 读取现有配置
        $config = require $configPath;
        
        // 修改数据库配置
        $config['db'] = [
            'driver' => 'custom',
            'custom_driver' => 'WebDAVDriver',
            'custom_driver_path' => __DIR__ . '/WebDAVDriver.php',
        ];
        
        // 保存修改后的配置
        file_put_contents($configPath, '<?php return ' . var_export($config, true) . ';');
    }
    
    /**
     * 创建MDClub配置文件
     * 
     * @param string $configPath 配置文件路径
     */
    private function createMDClubConfig($configPath) {
        // 创建基本配置
        $config = [
            'debug' => false,
            'site_url' => 'http://localhost/tayrep-web_副本/mdclub-v1/public',
            'timezone' => 'Asia/Shanghai',
            'db' => [
                'driver' => 'custom',
                'custom_driver' => 'WebDAVDriver',
                'custom_driver_path' => __DIR__ . '/WebDAVDriver.php',
            ],
            'cache' => [
                'driver' => 'file',
                'path' => __DIR__ . '/mdclub-v1/var/cache',
            ],
            'storage' => [
                'driver' => 'local',
                'path' => __DIR__ . '/mdclub-v1/public/upload',
                'url' => 'http://localhost/tayrep-web_副本/mdclub-v1/public/upload',
            ],
            'mail' => [
                'driver' => 'smtp',
                'host' => 'smtp.example.com',
                'port' => 465,
                'username' => 'username',
                'password' => 'password',
                'encryption' => 'ssl',
                'from' => [
                    'address' => 'noreply@example.com',
                    'name' => 'Taylor Swift Club',
                ],
            ],
        ];
        
        // 确保目录存在
        $dir = dirname($configPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // 保存配置
        file_put_contents($configPath, '<?php return ' . var_export($config, true) . ';');
    }
    
    /**
     * 创建索引文件
     */
    private function createIndexFiles() {
        // 为每种模型创建索引文件
        foreach ($this->modelMappings as $model => $mapping) {
            $this->createEmptyIndex($model);
        }
    }
    
    /**
     * 创建空索引文件
     * 
     * @param string $model 模型名称
     */
    private function createEmptyIndex($model) {
        $indexData = [
            'last_id' => 0,
            'count' => 0,
            'items' => [],
        ];
        
        $this->webdavAdapter->saveIndex($model, $indexData);
    }
    
    /**
     * 查询数据
     * 
     * @param string $model 模型名称
     * @param mixed $id ID或条件数组
     * @return array|null 查询结果
     */
    public function query($model, $id = null) {
        if (!isset($this->modelMappings[$model])) {
            return null;
        }
        
        if ($id === null) {
            // 查询所有记录
            return $this->webdavAdapter->getAll($model);
        } elseif (is_array($id)) {
            // 按条件查询
            return $this->webdavAdapter->getByConditions($model, $id);
        } else {
            // 按ID查询
            return $this->webdavAdapter->get($model, $id);
        }
    }
    
    /**
     * 保存数据
     * 
     * @param string $model 模型名称
     * @param array $data 数据
     * @return bool|string 是否成功或新ID
     */
    public function save($model, $data) {
        if (!isset($this->modelMappings[$model])) {
            return false;
        }
        
        // 如果没有ID，生成一个新ID
        if (!isset($data[$this->modelMappings[$model]['primaryKey']])) {
            $data[$this->modelMappings[$model]['primaryKey']] = $this->generateId($model);
        }
        
        // 添加创建时间和更新时间
        if (!isset($data['create_time'])) {
            $data['create_time'] = time();
        }
        $data['update_time'] = time();
        
        // 保存数据
        $result = $this->webdavAdapter->save($model, $data);
        
        if ($result) {
            return $data[$this->modelMappings[$model]['primaryKey']];
        }
        
        return false;
    }
    
    /**
     * 生成新ID
     * 
     * @param string $model 模型名称
     * @return string 新ID
     */
    private function generateId($model) {
        $index = $this->webdavAdapter->getIndex($model);
        $index['last_id']++;
        $index['count']++;
        $this->webdavAdapter->saveIndex($model, $index);
        return (string)$index['last_id'];
    }
    
    /**
     * 删除数据
     * 
     * @param string $model 模型名称
     * @param mixed $id ID或条件数组
     * @return bool 是否成功
     */
    public function delete($model, $id) {
        if (!isset($this->modelMappings[$model])) {
            return false;
        }
        
        if (is_array($id)) {
            // 按条件删除
            $items = $this->webdavAdapter->getByConditions($model, $id);
            $success = true;
            
            foreach ($items as $item) {
                $itemId = $item[$this->modelMappings[$model]['primaryKey']];
                $success = $success && $this->webdavAdapter->delete($model, $itemId);
            }
            
            return $success;
        } else {
            // 按ID删除
            return $this->webdavAdapter->delete($model, $id);
        }
    }
    
    /**
     * 更新数据
     * 
     * @param string $model 模型名称
     * @param mixed $id ID或条件数组
     * @param array $data 数据
     * @return bool 是否成功
     */
    public function update($model, $id, $data) {
        if (!isset($this->modelMappings[$model])) {
            return false;
        }
        
        if (is_array($id)) {
            // 按条件更新
            $items = $this->webdavAdapter->getByConditions($model, $id);
            $success = true;
            
            foreach ($items as $item) {
                $itemId = $item[$this->modelMappings[$model]['primaryKey']];
                $itemData = array_merge($item, $data);
                $itemData['update_time'] = time();
                $success = $success && $this->webdavAdapter->save($model, $itemData);
            }
            
            return $success;
        } else {
            // 按ID更新
            $item = $this->webdavAdapter->get($model, $id);
            
            if ($item) {
                $itemData = array_merge($item, $data);
                $itemData['update_time'] = time();
                return $this->webdavAdapter->save($model, $itemData);
            }
            
            return false;
        }
    }
    
    /**
     * 计数
     * 
     * @param string $model 模型名称
     * @param array $conditions 条件
     * @return int 计数
     */
    public function count($model, $conditions = []) {
        if (!isset($this->modelMappings[$model])) {
            return 0;
        }
        
        if (empty($conditions)) {
            // 获取总计数
            $index = $this->webdavAdapter->getIndex($model);
            return $index['count'];
        } else {
            // 按条件计数
            $items = $this->webdavAdapter->getByConditions($model, $conditions);
            return count($items);
        }
    }
}
