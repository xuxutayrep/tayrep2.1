<?php
/**
 * MDClub论坛与Taylor Swift Club网站集成配置文件
 * 
 * 此文件用于配置MDClub论坛系统与Taylor Swift Club网站的集成
 */

// 网站基本信息
$config = [
    // 网站信息
    'site' => [
        'name' => 'Taylor Swift Club 论坛',
        'description' => 'Taylor Swift粉丝交流社区',
        'keywords' => 'Taylor Swift, 泰勒斯威夫特, 粉丝社区, 论坛',
        'logo' => '../logo.png', // 相对于MDClub根目录的路径
    ],
    
    // 主题设置
    'theme' => [
        'primary_color' => '#2c3e50', // 主色调，与Taylor Swift Club网站保持一致
        'accent_color' => '#bfa14a', // 强调色，与Taylor Swift Club网站保持一致
        'font_family' => '"Roboto", sans-serif', // 字体
    ],
    
    // 集成设置
    'integration' => [
        'parent_site_url' => 'http://localhost/tayrep-web_副本/', // Taylor Swift Club网站URL
        'header_links' => [
            ['title' => '首页', 'url' => '../index.html'],
            ['title' => '最新专辑', 'url' => '../index.html#latest-album'],
            ['title' => '官方照片', 'url' => '../index.html#photos'],
            ['title' => '视频作品', 'url' => '../index.html#videos'],
            ['title' => '音乐作品', 'url' => '../index.html#music'],
            ['title' => '商店', 'url' => '../store.html'],
        ],
    ],
    
    // 用户系统集成（未来可扩展为单点登录）
    'user' => [
        'shared_login' => false, // 是否启用单点登录
        'login_url' => '', // 登录页面URL
        'register_url' => '', // 注册页面URL
    ],
];

return $config;
