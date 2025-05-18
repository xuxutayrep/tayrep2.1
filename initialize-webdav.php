<?php
/**
 * WebDAV存储初始化脚本
 * 
 * 此脚本用于初始化WebDAV存储结构并测试连接
 */

// 加载必要的文件
require_once __DIR__ . '/WebDAVAdapter.php';
require_once __DIR__ . '/MDClubWebDAVIntegration.php';

// 输出函数
function output($message) {
    echo $message . PHP_EOL;
    flush();
}

// 错误处理
function handleError($message, $exception = null) {
    output('错误: ' . $message);
    if ($exception) {
        output('异常信息: ' . $exception->getMessage());
    }
    exit(1);
}

try {
    output('开始初始化WebDAV存储...');
    
    // 加载配置
    output('加载WebDAV配置...');
    $config = require __DIR__ . '/webdav-config.php';
    
    // 创建WebDAV适配器
    output('创建WebDAV适配器...');
    $adapter = new WebDAVAdapter($config);
    
    // 测试连接
    output('测试WebDAV连接...');
    $testResult = $adapter->testConnection();
    
    if (!$testResult) {
        handleError('无法连接到WebDAV服务器，请检查配置信息');
    }
    
    output('WebDAV连接成功！');
    
    // 创建存储结构
    output('创建存储目录结构...');
    
    // 确保根目录存在
    $rootPath = $config['server']['root_path'];
    if (!$adapter->ensureDirectoryExists($rootPath)) {
        handleError('无法创建根目录: ' . $rootPath);
    }
    
    // 创建数据目录
    foreach ($config['storage'] as $dirName => $dirPath) {
        $fullPath = $rootPath . $dirPath;
        output('创建目录: ' . $fullPath);
        
        if (!$adapter->ensureDirectoryExists($fullPath)) {
            handleError('无法创建目录: ' . $fullPath);
        }
    }
    
    output('存储目录结构创建成功！');
    
    // 初始化MDClub集成
    output('初始化MDClub与WebDAV集成...');
    $integration = MDClubWebDAVIntegration::getInstance();
    $installResult = $integration->install();
    
    if (!$installResult) {
        handleError('MDClub集成安装失败');
    }
    
    output('MDClub与WebDAV集成初始化成功！');
    
    // 创建测试数据
    output('创建测试数据...');
    
    // 创建测试用户
    $testUser = [
        'user_id' => '1',
        'username' => 'taylor_fan',
        'email' => 'taylor_fan@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'avatar' => 'default_avatar.jpg',
        'create_time' => time(),
        'update_time' => time(),
        'status' => 'active'
    ];
    
    $userId = $integration->save('user', $testUser);
    if (!$userId) {
        handleError('无法创建测试用户');
    }
    
    output('测试用户创建成功，ID: ' . $userId);
    
    // 创建测试话题
    $testTopic = [
        'topic_id' => '1',
        'name' => 'Taylor Swift 最新专辑',
        'description' => '讨论Taylor Swift的最新专辑和单曲',
        'create_time' => time(),
        'update_time' => time(),
        'follow_count' => 0,
        'article_count' => 0,
        'question_count' => 0
    ];
    
    $topicId = $integration->save('topic', $testTopic);
    if (!$topicId) {
        handleError('无法创建测试话题');
    }
    
    output('测试话题创建成功，ID: ' . $topicId);
    
    // 创建测试帖子
    $testArticle = [
        'article_id' => '1',
        'user_id' => $userId,
        'title' => '欢迎来到Taylor Swift Club论坛',
        'content' => "# 欢迎来到Taylor Swift Club论坛\n\n这是一个专为Taylor Swift粉丝创建的社区，在这里你可以：\n\n* 讨论Taylor的音乐和表演\n* 分享你喜欢的歌曲和MV\n* 交流演唱会体验\n* 结识其他Swifties\n\n期待你的参与！",
        'create_time' => time(),
        'update_time' => time(),
        'comment_count' => 0,
        'view_count' => 0,
        'vote_count' => 0
    ];
    
    $articleId = $integration->save('article', $testArticle);
    if (!$articleId) {
        handleError('无法创建测试帖子');
    }
    
    output('测试帖子创建成功，ID: ' . $articleId);
    
    // 完成
    output('');
    output('WebDAV存储初始化完成！');
    output('论坛数据现在将存储在您的123云盘WebDAV服务上。');
    output('');
    output('您可以通过访问以下地址来使用论坛：');
    output('http://localhost/tayrep-web_副本/forum.html');
    
} catch (Exception $e) {
    handleError('初始化过程中发生异常', $e);
}
