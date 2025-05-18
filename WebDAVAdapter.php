<?php
/**
 * WebDAV适配器类
 * 
 * 此类用于将MDClub的数据库操作转换为WebDAV文件操作
 * 实现了一个简单的ORM层，将对象映射到WebDAV文件系统
 */

class WebDAVAdapter {
    private $client;
    private $config;
    private $cache = [];
    
    /**
     * 构造函数
     * 
     * @param array $config WebDAV配置
     */
    public function __construct($config = null) {
        if ($config === null) {
            $config = require __DIR__ . '/webdav-config.php';
        }
        
        $this->config = $config;
        $this->initClient();
    }
    
    /**
     * 初始化WebDAV客户端
     */
    private function initClient() {
        // 这里使用PHP的SabreDAV库，您需要通过Composer安装它
        // composer require sabre/dav
        
        // 如果没有安装SabreDAV，这里提供一个简单的WebDAV客户端实现
        $this->client = new SimpleWebDAVClient(
            $this->config['server']['url'],
            $this->config['server']['username'],
            $this->config['server']['password']
        );
    }
    
    /**
     * 测试WebDAV连接
     * 
     * @return bool 连接是否成功
     */
    public function testConnection() {
        try {
            // 尝试连接并检查服务器响应
            $response = $this->client->request('PROPFIND', '', null, ['Depth: 0']);
            return $response !== false;
        } catch (\Exception $e) {
            // 记录错误信息
            error_log('WebDAV连接错误: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 创建存储结构
     * 
     * @return bool 是否成功
     */
    public function createStorageStructure() {
        try {
            // 确保根目录存在
            if (!$this->ensureDirectoryExists($this->config['server']['root_path'])) {
                return false;
            }
            
            // 确保所有数据目录存在
            foreach ($this->config['storage'] as $dir) {
                if (!$this->ensureDirectoryExists($this->config['server']['root_path'] . $dir)) {
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('WebDAV创建存储结构错误: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 确保目录存在，如果不存在则创建
     * 
     * @param string $path 目录路径
     * @return bool 是否成功
     */
    private function ensureDirectoryExists($path) {
        if (!$this->client->exists($path)) {
            return $this->client->createDirectory($path);
        }
        return true;
    }
    
    /**
     * 保存用户数据
     * 
     * @param array $userData 用户数据
     * @return bool 是否成功
     */
    public function saveUser($userData) {
        $userId = $userData['id'] ?? uniqid('user_');
        $path = $this->config['server']['root_path'] . $this->config['storage']['users'] . $userId . '.json';
        
        // 转换为JSON
        $content = json_encode($userData, JSON_PRETTY_PRINT);
        
        // 如果启用了压缩
        if ($this->config['format']['compress']) {
            $content = gzencode($content);
        }
        
        // 如果启用了加密（这里仅为示例，实际应用中应使用更安全的加密方法）
        if ($this->config['format']['encrypt']) {
            $content = base64_encode($content);
        }
        
        // 保存到WebDAV
        $result = $this->client->put($path, $content);
        
        // 更新缓存
        if ($result && $this->config['cache']['enabled']) {
            $this->cache['users'][$userId] = $userData;
        }
        
        return $result;
    }
    
    /**
     * 获取用户数据
     * 
     * @param string $userId 用户ID
     * @return array|null 用户数据或null（如果不存在）
     */
    public function getUser($userId) {
        // 检查缓存
        if ($this->config['cache']['enabled'] && isset($this->cache['users'][$userId])) {
            return $this->cache['users'][$userId];
        }
        
        $path = $this->config['server']['root_path'] . $this->config['storage']['users'] . $userId . '.json';
        
        // 检查文件是否存在
        if (!$this->client->exists($path)) {
            return null;
        }
        
        // 获取内容
        $content = $this->client->get($path);
        
        // 如果启用了加密
        if ($this->config['format']['encrypt']) {
            $content = base64_decode($content);
        }
        
        // 如果启用了压缩
        if ($this->config['format']['compress']) {
            $content = gzdecode($content);
        }
        
        // 解析JSON
        $userData = json_decode($content, true);
        
        // 更新缓存
        if ($this->config['cache']['enabled']) {
            $this->cache['users'][$userId] = $userData;
        }
        
        return $userData;
    }
    
    /**
     * 保存帖子数据
     * 
     * @param array $postData 帖子数据
     * @return bool 是否成功
     */
    public function savePost($postData) {
        $postId = $postData['id'] ?? uniqid('post_');
        $path = $this->config['server']['root_path'] . $this->config['storage']['posts'] . $postId . '.json';
        
        // 转换为JSON
        $content = json_encode($postData, JSON_PRETTY_PRINT);
        
        // 如果启用了压缩
        if ($this->config['format']['compress']) {
            $content = gzencode($content);
        }
        
        // 如果启用了加密
        if ($this->config['format']['encrypt']) {
            $content = base64_encode($content);
        }
        
        // 保存到WebDAV
        $result = $this->client->put($path, $content);
        
        // 更新缓存
        if ($result && $this->config['cache']['enabled']) {
            $this->cache['posts'][$postId] = $postData;
        }
        
        // 更新索引
        $this->updateIndex('posts', $postData);
        
        return $result;
    }
    
    /**
     * 获取帖子数据
     * 
     * @param string $postId 帖子ID
     * @return array|null 帖子数据或null（如果不存在）
     */
    public function getPost($postId) {
        // 检查缓存
        if ($this->config['cache']['enabled'] && isset($this->cache['posts'][$postId])) {
            return $this->cache['posts'][$postId];
        }
        
        $path = $this->config['server']['root_path'] . $this->config['storage']['posts'] . $postId . '.json';
        
        // 检查文件是否存在
        if (!$this->client->exists($path)) {
            return null;
        }
        
        // 获取内容
        $content = $this->client->get($path);
        
        // 如果启用了加密
        if ($this->config['format']['encrypt']) {
            $content = base64_decode($content);
        }
        
        // 如果启用了压缩
        if ($this->config['format']['compress']) {
            $content = gzdecode($content);
        }
        
        // 解析JSON
        $postData = json_decode($content, true);
        
        // 更新缓存
        if ($this->config['cache']['enabled']) {
            $this->cache['posts'][$postId] = $postData;
        }
        
        return $postData;
    }
    
    /**
     * 获取所有帖子
     * 
     * @param int $offset 偏移量
     * @param int $limit 限制数量
     * @param array $filters 过滤条件
     * @return array 帖子列表
     */
    public function getAllPosts($offset = 0, $limit = 20, $filters = []) {
        $postsDir = $this->config['server']['root_path'] . $this->config['storage']['posts'];
        $files = $this->client->listDirectory($postsDir);
        
        $posts = [];
        $count = 0;
        
        foreach ($files as $file) {
            // 跳过目录
            if (substr($file, -5) !== '.json') {
                continue;
            }
            
            // 应用偏移量
            if ($count < $offset) {
                $count++;
                continue;
            }
            
            // 应用限制
            if (count($posts) >= $limit) {
                break;
            }
            
            $postId = basename($file, '.json');
            $post = $this->getPost($postId);
            
            // 应用过滤器
            $include = true;
            foreach ($filters as $key => $value) {
                if (!isset($post[$key]) || $post[$key] != $value) {
                    $include = false;
                    break;
                }
            }
            
            if ($include) {
                $posts[] = $post;
            }
        }
        
        return $posts;
    }
    
    /**
     * 更新索引
     * 
     * @param string $type 索引类型
     * @param array $data 数据
     */
    private function updateIndex($type, $data) {
        $indexPath = $this->config['server']['root_path'] . $this->config['storage']['index'] . $type . '.json';
        
        // 获取现有索引
        $index = [];
        if ($this->client->exists($indexPath)) {
            $content = $this->client->get($indexPath);
            $index = json_decode($content, true) ?: [];
        }
        
        // 更新索引
        $id = $data['id'];
        $index[$id] = [
            'id' => $id,
            'title' => $data['title'] ?? '',
            'created_at' => $data['created_at'] ?? time(),
            'updated_at' => $data['updated_at'] ?? time(),
            'user_id' => $data['user_id'] ?? '',
        ];
        
        // 保存索引
        $content = json_encode($index, JSON_PRETTY_PRINT);
        $this->client->put($indexPath, $content);
    }
    
    /**
     * 保存索引
     * 
     * @param string $model 模型名称
     * @param array $indexData 索引数据
     * @return bool 是否成功
     */
    public function saveIndex($model, $indexData) {
        $indexPath = $this->config['server']['root_path'] . $this->config['storage']['index'] . $model . '.json';
        $content = json_encode($indexData, JSON_PRETTY_PRINT);
        return $this->client->put($indexPath, $content) !== false;
    }
    
    /**
     * 获取索引
     * 
     * @param string $model 模型名称
     * @return array 索引数据
     */
    public function getIndex($model) {
        $indexPath = $this->config['server']['root_path'] . $this->config['storage']['index'] . $model . '.json';
        
        if (!$this->client->exists($indexPath)) {
            // 如果索引不存在，创建一个空索引
            $emptyIndex = [
                'last_id' => 0,
                'count' => 0,
                'items' => [],
            ];
            $this->saveIndex($model, $emptyIndex);
            return $emptyIndex;
        }
        
        $content = $this->client->get($indexPath);
        $index = json_decode($content, true);
        
        if (!is_array($index)) {
            // 如果解析失败，返回空索引
            return [
                'last_id' => 0,
                'count' => 0,
                'items' => [],
            ];
        }
        
        return $index;
    }
    
    /**
     * 搜索帖子
     * 
     * @param string $keyword 关键词
     * @param int $offset 偏移量
     * @param int $limit 限制数量
     * @return array 搜索结果
     */
    public function searchPosts($keyword, $offset = 0, $limit = 20) {
        $indexPath = $this->config['server']['root_path'] . $this->config['storage']['index'] . 'posts.json';
        
        // 获取索引
        if (!$this->client->exists($indexPath)) {
            return [];
        }
        
        $content = $this->client->get($indexPath);
        $index = json_decode($content, true) ?: [];
        
        // 搜索
        $results = [];
        foreach ($index as $id => $item) {
            if (stripos($item['title'], $keyword) !== false) {
                $results[] = $this->getPost($id);
            }
            
            if (count($results) >= $offset + $limit) {
                break;
            }
        }
        
        // 应用偏移量和限制
        return array_slice($results, $offset, $limit);
    }
}

/**
 * 简单的WebDAV客户端实现
 * 
 * 这是一个基本的WebDAV客户端实现，用于演示目的
 * 在实际应用中，建议使用成熟的WebDAV库，如SabreDAV
 */
class SimpleWebDAVClient {
    private $baseUrl;
    private $username;
    private $password;
    
    /**
     * 构造函数
     * 
     * @param string $baseUrl WebDAV服务器URL
     * @param string $username 用户名
     * @param string $password 密码
     */
    public function __construct($baseUrl, $username, $password) {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
        $this->username = $username;
        $this->password = $password;
    }
    
    /**
     * 发送HTTP请求
     * 
     * @param string $method HTTP方法
     * @param string $path 路径
     * @param string $data 数据
     * @param array $headers 请求头
     * @return mixed 响应内容或false（如果失败）
     */
    public function request($method, $path, $data = null, $headers = []) {
        $url = $this->baseUrl . ltrim($path, '/');
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对于自签名证书，禁用证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 禁用主机验证
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时时间
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 跟随重定向
        
        // 添加特定于123云盘的头信息
        $defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
            'Accept: */*',
        ];
        
        // 如果是PUT请求，添加Content-Type头
        if ($method === 'PUT' && $data !== null) {
            $defaultHeaders[] = 'Content-Type: application/octet-stream';
            $defaultHeaders[] = 'Content-Length: ' . strlen($data);
        }
        
        // 合并头信息
        $allHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // 如果出错，记录错误信息
        if (curl_errno($ch)) {
            error_log('WebDAV请求错误: ' . curl_error($ch) . ' URL: ' . $url);
        }
        
        curl_close($ch);
        
        // 对于某些操作，即使返回码不是2xx也可能是成功的
        // 例如，MKCOL如果目录已存在可能返回405
        if ($method === 'MKCOL' && $httpCode === 405) {
            return true; // 目录已存在
        }
        
        // 对于PROPFIND，返回码207表示成功
        if ($method === 'PROPFIND' && $httpCode === 207) {
            return $response;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $response;
        }
        
        return false;
    }
    
    /**
     * 检查路径是否存在
     * 
     * @param string $path 路径
     * @return bool 是否存在
     */
    public function exists($path) {
        $response = $this->request('PROPFIND', $path, null, ['Depth: 0']);
        return $response !== false;
    }
    
    /**
     * 创建目录
     * 
     * @param string $path 目录路径
     * @return bool 是否成功
     */
    public function createDirectory($path) {
        $response = $this->request('MKCOL', $path);
        return $response !== false;
    }
    
    /**
     * 上传文件
     * 
     * @param string $path 文件路径
     * @param string $content 文件内容
     * @return bool 是否成功
     */
    public function put($path, $content) {
        $response = $this->request('PUT', $path, $content);
        return $response !== false;
    }
    
    /**
     * 下载文件
     * 
     * @param string $path 文件路径
     * @return string|false 文件内容或false（如果失败）
     */
    public function get($path) {
        return $this->request('GET', $path);
    }
    
    /**
     * 列出目录内容
     * 
     * @param string $path 目录路径
     * @return array 文件列表
     */
    public function listDirectory($path) {
        $response = $this->request('PROPFIND', $path, null, ['Depth: 1']);
        
        if ($response === false) {
            return [];
        }
        
        // 解析XML响应
        $xml = simplexml_load_string($response);
        if ($xml === false) {
            return [];
        }
        
        $files = [];
        foreach ($xml->response as $response) {
            $href = (string)$response->href;
            $name = basename($href);
            
            // 跳过当前目录
            if ($name === basename(rtrim($path, '/'))) {
                continue;
            }
            
            $files[] = $name;
        }
        
        return $files;
    }
    
    /**
     * 删除文件或目录
     * 
     * @param string $path 路径
     * @return bool 是否成功
     */
    public function delete($path) {
        $response = $this->request('DELETE', $path);
        return $response !== false;
    }
}
