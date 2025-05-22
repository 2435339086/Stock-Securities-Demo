<?php
/**
 * 后台管理页面
 */
session_start();
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/models/UserModel.php';
require_once __DIR__ . '/../src/models/AssetModel.php';
require_once __DIR__ . '/../src/models/OrderModel.php';

// 检查用户是否已登录且是管理员
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 获取用户操作日志
function getUserLogs($limit = 100) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM user_logs ORDER BY create_time DESC LIMIT :limit";
    return $db->select($sql, [':limit' => $limit]);
}

// 获取资产列表
$assetModel = new AssetModel();
$assets = $assetModel->findAll();

// 获取未完成订单
$orderModel = new OrderModel();
$pendingOrders = $orderModel->findAll(['status' => 'pending']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>后台管理</title>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f2f2f2;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .error-message {
            color: red;
            margin-bottom: 10px;
        }
        .success-message {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>后台管理</h1>
    
    <div class="container">
        <div class="section">
            <h2>用户操作日志</h2>
            <table>
                <thead>
                    <tr>
                        <th>用户ID</th>
                        <th>操作类型</th>
                        <th>详情</th>
                        <th>时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (getUserLogs() as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                        <td><?php echo $log['create_time']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>资产价格设置</h2>
            <div id="assetMessage"></div>
            <form id="assetForm">
                <div class="form-group">
                    <label for="asset_pair">资产对</label>
                    <select name="asset_pair" required>
                        <?php foreach ($assets as $asset): ?>
                        <option value="<?php echo htmlspecialchars($asset['name']); ?>">
                            <?php echo htmlspecialchars($asset['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="volatility">波动范围（%）</label>
                    <input type="number" name="volatility" min="0" max="100" step="0.1" required>
                </div>
                
                <button type="submit">更新设置</button>
            </form>
        </div>
        
        <div class="section">
            <h2>订单管理</h2>
            <div id="orderMessage"></div>
            <table>
                <thead>
                    <tr>
                        <th>订单ID</th>
                        <th>用户ID</th>
                        <th>资产对</th>
                        <th>方向</th>
                        <th>价格</th>
                        <th>数量</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingOrders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['asset_pair']); ?></td>
                        <td><?php echo $order['direction']; ?></td>
                        <td><?php echo $order['price']; ?></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>
                            <button onclick="cancelOrder('<?php echo $order['order_id']; ?>')">取消订单</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // 更新资产价格设置
        $('#assetForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                asset_pair: $('select[name="asset_pair"]').val(),
                volatility: $('input[name="volatility"]').val()
            };
            
            $.ajax({
                url: '/api/admin/update_asset',
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        $('#assetMessage').html('<div class="success-message">设置更新成功！</div>');
                    } else {
                        $('#assetMessage').html(`<div class="error-message">${response.message}</div>`);
                    }
                },
                error: function() {
                    $('#assetMessage').html('<div class="error-message">更新失败，请稍后重试</div>');
                }
            });
        });
        
        // 取消订单
        function cancelOrder(orderId) {
            if (confirm('确定要取消这个订单吗？')) {
                $.ajax({
                    url: '/api/admin/cancel_order',
                    method: 'POST',
                    data: JSON.stringify({ order_id: orderId }),
                    contentType: 'application/json',
                    success: function(response) {
                        if (response.success) {
                            $('#orderMessage').html('<div class="success-message">订单已取消！</div>');
                            location.reload();
                        } else {
                            $('#orderMessage').html(`<div class="error-message">${response.message}</div>`);
                        }
                    },
                    error: function() {
                        $('#orderMessage').html('<div class="error-message">操作失败，请稍后重试</div>');
                    }
                });
            }
        }
    </script>
</body>
</html> 