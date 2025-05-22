<?php
/**
 * 登录后的首页
 */
session_start();
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/models/UserModel.php';
require_once __DIR__ . '/../src/models/AssetModel.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取当前登录用户信息
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 获取用户详细信息
$userModel = new UserModel();
$user = $userModel->findById($user_id);

// 获取资产列表
$assetModel = new AssetModel();
$assets = $assetModel->findAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>交易所 - 个人中心</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        h1, h2 {
            color: #333;
        }
        .user-info {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .asset-list {
            width: 100%;
            border-collapse: collapse;
        }
        .asset-list th, .asset-list td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .asset-list th {
            background-color: #f2f2f2;
        }
        .asset-list tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
        .logout-btn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <header>
        <h1>交易所 - 个人中心</h1>
        <a href="logout.php" class="logout-btn">退出登录</a>
    </header>
    
    <div class="user-info">
        <h2>欢迎回来，<?php echo htmlspecialchars($username); ?>！</h2>
        <p>账户余额: <?php echo number_format($user['balance'], 8); ?> USDT</p>
        <p>注册时间: <?php echo $user['register_time']; ?></p>
    </div>
    
    <h2>可交易资产</h2>
    <table class="asset-list">
        <thead>
            <tr>
                <th>资产名称</th>
                <th>类型</th>
                <th>当前价格</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assets as $asset): ?>
            <tr>
                <td><?php echo htmlspecialchars($asset['name']); ?></td>
                <td><?php echo $asset['type'] == 'spot' ? '现货' : '合约'; ?></td>
                <td><?php echo number_format($asset['current_price'], 2); ?> USDT</td>
                <td>
                    <a href="#">交易</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html> 