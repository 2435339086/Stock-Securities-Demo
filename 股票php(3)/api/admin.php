<?php
/**
 * 管理API端点
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/models/AssetModel.php';
require_once __DIR__ . '/../src/models/OrderModel.php';

// 检查管理员权限
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => '无权限访问'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取请求方法和路径
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// 解析路径参数
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// 移除前面的'api/admin'部分
if (count($pathParts) > 0 && $pathParts[0] === 'api') {
    array_shift($pathParts);
}
if (count($pathParts) > 0 && $pathParts[0] === 'admin') {
    array_shift($pathParts);
}

// 获取请求参数
$params = [];
if ($requestMethod === 'GET') {
    $params = $_GET;
} else {
    $requestBody = file_get_contents('php://input');
    if (!empty($requestBody)) {
        $params = json_decode($requestBody, true) ?? [];
    }
}

// 路由处理
$response = ['success' => false, 'message' => '未找到请求的资源'];

// 主资源类型
$resourceType = $pathParts[0] ?? '';

switch ($resourceType) {
    case 'update_asset':
        if ($requestMethod === 'POST') {
            if (!isset($params['asset_pair']) || !isset($params['volatility'])) {
                $response = ['success' => false, 'message' => '缺少必要参数'];
            } else {
                $assetModel = new AssetModel();
                $result = $assetModel->updateVolatility($params['asset_pair'], $params['volatility']);
                $response = $result;
            }
        }
        break;
        
    case 'cancel_order':
        if ($requestMethod === 'POST') {
            if (!isset($params['order_id'])) {
                $response = ['success' => false, 'message' => '缺少订单ID'];
            } else {
                $orderModel = new OrderModel();
                $result = $orderModel->adminCancelOrder($params['order_id']);
                $response = $result;
            }
        }
        break;
        
    case 'user_logs':
        if ($requestMethod === 'GET') {
            $limit = $params['limit'] ?? 100;
            $db = Database::getInstance();
            $sql = "SELECT * FROM user_logs ORDER BY create_time DESC LIMIT :limit";
            $logs = $db->select($sql, [':limit' => $limit]);
            $response = [
                'success' => true,
                'logs' => $logs
            ];
        }
        break;
        
    default:
        $response = [
            'success' => false,
            'message' => '无效的请求'
        ];
        break;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE); 