<?php
/**
 * WebDAV配置文件
 * 用于配置WebDAV服务器连接信息和数据存储结构
 */

return [
    // WebDAV服务器配置
    'server' => [
        'url' => 'https://webdav-1816215268.pd1.123pan.cn/webdav/', // WebDAV服务器URL
        'username' => '13306671181', // WebDAV用户名
        'password' => '1iqey301000d9a5sj2spmjjxz6btb5s0', // WebDAV密码
        'root_path' => 'taylor-swift-forum/', // 根目录路径
        'timeout' => 30, // 连接超时时间（秒）
    ],
    
    // 数据存储结构
    'storage' => [
        'users' => 'users/', // 用户数据目录
        'posts' => 'posts/', // 帖子数据目录
        'topics' => 'topics/', // 话题数据目录
        'comments' => 'comments/', // 评论数据目录
        'attachments' => 'attachments/', // 附件数据目录
        'cache' => 'cache/', // 缓存目录
        'index' => 'index/', // 索引目录（用于搜索）
    ],
    
    // 文件格式设置
    'format' => [
        'data_format' => 'json', // 数据存储格式（json或xml）
        'compress' => true, // 是否压缩数据
        'encrypt' => false, // 是否加密数据
    ],
    
    // 缓存设置
    'cache' => [
        'enabled' => true, // 是否启用本地缓存
        'lifetime' => 3600, // 缓存生存时间（秒）
        'path' => '../var/cache/webdav/', // 本地缓存路径
    ],
    
    // 同步设置
    'sync' => [
        'interval' => 300, // 同步间隔（秒）
        'retry_count' => 3, // 失败重试次数
        'log_path' => '../var/logs/webdav_sync.log', // 同步日志路径
    ],
];
