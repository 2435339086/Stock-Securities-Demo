<?php
/**
 * 数据库初始化脚本
 */

try {
    $config = require_once __DIR__ . '/config/database.php';
    
    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // 创建数据库
    $sql = "CREATE DATABASE IF NOT EXISTS {$config['dbname']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $pdo->exec($sql);
    
    echo "数据库创建成功\n";
    
    // 使用新创建的数据库
    $pdo->exec("USE {$config['dbname']}");
    
    // 创建用户表
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        balance DECIMAL(18,8) DEFAULT 0,
        register_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username (username)
    )";
    $pdo->exec($sql);
    
    echo "用户表创建成功\n";
    
    // 创建资产表
    $sql = "CREATE TABLE IF NOT EXISTS assets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(20) NOT NULL UNIQUE COMMENT '如BTC/USDT',
        type ENUM('spot', 'futures') NOT NULL COMMENT '现货/合约',
        current_price DECIMAL(18,8) NOT NULL,
        volatility DECIMAL(5,2) DEFAULT 5.0 COMMENT '价格波动率',
        INDEX idx_name (name)
    )";
    $pdo->exec($sql);
    
    echo "资产表创建成功\n";
    
    // 创建订单表
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        order_id VARCHAR(50) PRIMARY KEY,
        user_id INT NOT NULL,
        asset_pair VARCHAR(20) NOT NULL,
        type ENUM('limit', 'market') NOT NULL COMMENT '限价/市价',
        direction ENUM('buy', 'sell') NOT NULL COMMENT '买/卖',
        price DECIMAL(18,8) NOT NULL,
        quantity DECIMAL(18,8) NOT NULL,
        status ENUM('pending', 'filled', 'canceled') NOT NULL DEFAULT 'pending' COMMENT '未成交/已成交/已取消',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_asset_pair (asset_pair),
        INDEX idx_status (status)
    )";
    $pdo->exec($sql);
    
    echo "订单表创建成功\n";
    
    // 创建K线数据表
    $sql = "CREATE TABLE IF NOT EXISTS kline (
        asset_pair VARCHAR(20) NOT NULL,
        timestamp BIGINT NOT NULL,
        open DECIMAL(18,8) NOT NULL,
        high DECIMAL(18,8) NOT NULL,
        low DECIMAL(18,8) NOT NULL,
        close DECIMAL(18,8) NOT NULL,
        volume DECIMAL(18,8) NOT NULL DEFAULT 0,
        PRIMARY KEY (asset_pair, timestamp),
        INDEX idx_asset_timestamp (asset_pair, timestamp)
    )";
    $pdo->exec($sql);
    
    echo "K线数据表创建成功\n";
    
    // 创建帖子表
    $sql = "CREATE TABLE IF NOT EXISTS posts (
        post_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        asset_pair VARCHAR(20) NULL,
        content TEXT NOT NULL,
        publish_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_asset_pair (asset_pair),
        INDEX idx_publish_time (publish_time)
    )";
    $pdo->exec($sql);
    
    echo "帖子表创建成功\n";
    
    // 创建合约仓位表
    $sql = "CREATE TABLE IF NOT EXISTS futures_positions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        asset_pair VARCHAR(20) NOT NULL,
        direction ENUM('long', 'short') NOT NULL COMMENT '做多/做空',
        entry_price DECIMAL(18,8) NOT NULL COMMENT '入场价格',
        quantity DECIMAL(18,8) NOT NULL COMMENT '数量',
        margin DECIMAL(18,8) NOT NULL COMMENT '保证金',
        liquidation_price DECIMAL(18,8) NOT NULL COMMENT '强平价格',
        status ENUM('open', 'closed', 'liquidated') NOT NULL DEFAULT 'open' COMMENT '开仓/平仓/强平',
        exit_price DECIMAL(18,8) NULL COMMENT '出场价格',
        pnl DECIMAL(18,8) NULL COMMENT '盈亏',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        close_time DATETIME NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_asset_pair (asset_pair),
        INDEX idx_status (status)
    )";
    $pdo->exec($sql);
    
    echo "合约仓位表创建成功\n";
    
    // 创建用户操作日志表
    $sql = "CREATE TABLE IF NOT EXISTS user_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL COMMENT '操作类型',
        details TEXT NOT NULL COMMENT '操作详情',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_action_type (action_type)
    )";
    $pdo->exec($sql);
    
    echo "用户操作日志表创建成功\n";
    
    // 创建管理员操作日志表
    $sql = "CREATE TABLE IF NOT EXISTS admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action_type VARCHAR(50) NOT NULL COMMENT '操作类型',
        details TEXT NOT NULL COMMENT '操作详情',
        create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action_type (action_type)
    )";
    $pdo->exec($sql);
    
    echo "管理员操作日志表创建成功\n";
    
    // 添加一些初始数据
    
    // 添加管理员用户
    $sql = "INSERT INTO users (username, password_hash, balance) 
            VALUES ('admin', :password_hash, 10000)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':password_hash' => password_hash('admin123', PASSWORD_DEFAULT)]);
    
    // 添加一些资产
    $assets = [
        ['BTC/USDT', 'spot', 65000],
        ['ETH/USDT', 'spot', 3500],
        ['BNB/USDT', 'spot', 600],
        ['SOL/USDT', 'spot', 150],
        ['BTC/USDT-PERP', 'futures', 65000],
        ['ETH/USDT-PERP', 'futures', 3500]
    ];
    
    $sql = "INSERT INTO assets (name, type, current_price) VALUES (:name, :type, :price)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($assets as $asset) {
        $stmt->execute([
            ':name' => $asset[0],
            ':type' => $asset[1],
            ':price' => $asset[2]
        ]);
    }
    
    echo "初始数据添加成功\n";
    echo "数据库初始化完成\n";
    
} catch (PDOException $e) {
    die("错误: " . $e->getMessage() . "\n");
} 