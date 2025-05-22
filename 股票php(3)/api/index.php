<?php
/**
 * API入口文件
 */

// 允许跨域请求
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// 引入数据库连接
require_once __DIR__ . '/../src/Database.php';

// 自动加载模型
$modelDir = __DIR__ . '/../src/models/';
$modelFiles = glob($modelDir . '*.php');
foreach ($modelFiles as $modelFile) {
    require_once $modelFile;
}

// 获取请求方法和路径
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// 解析路径参数
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// 移除前面的'api'部分
if (count($pathParts) > 0 && $pathParts[0] === 'api') {
    array_shift($pathParts);
}

// 获取请求参数
$params = [];
if ($requestMethod === 'GET') {
    $params = $_GET;
} else {
    // 获取原始请求体
    $requestBody = file_get_contents('php://input');
    if (!empty($requestBody)) {
        $params = json_decode($requestBody, true) ?? [];
    }
    
    // 合并POST参数
    if (!empty($_POST)) {
        $params = array_merge($params, $_POST);
    }
}

// 路由处理
$response = ['success' => false, 'message' => '未找到请求的资源'];

// 主资源类型
$resourceType = $pathParts[0] ?? '';

// 简单路由处理
switch ($resourceType) {
    case 'users':
        $userModel = new UserModel();
        
        // 路由: /api/users/register [POST]
        if ($requestMethod === 'POST' && isset($pathParts[1]) && $pathParts[1] === 'register') {
            if (!isset($params['username']) || !isset($params['password'])) {
                $response = ['success' => false, 'message' => '缺少必要参数'];
            } else {
                $response = $userModel->register($params['username'], $params['password']);
            }
        }
        // 路由: /api/users/login [POST]
        else if ($requestMethod === 'POST' && isset($pathParts[1]) && $pathParts[1] === 'login') {
            if (!isset($params['username']) || !isset($params['password'])) {
                $response = ['success' => false, 'message' => '缺少必要参数'];
            } else {
                $response = $userModel->login($params['username'], $params['password']);
            }
        }
        // 路由: /api/users/{id} [GET]
        else if ($requestMethod === 'GET' && isset($pathParts[1]) && is_numeric($pathParts[1])) {
            $user = $userModel->findById($pathParts[1]);
            if ($user) {
                // 不返回密码哈希
                unset($user['password_hash']);
                $response = ['success' => true, 'user' => $user];
            } else {
                $response = ['success' => false, 'message' => '用户不存在'];
            }
        }
        break;
        
    case 'assets':
        $assetModel = new AssetModel();
        
        // 路由: /api/assets [GET]
        if ($requestMethod === 'GET' && !isset($pathParts[1])) {
            $type = $params['type'] ?? null;
            
            if ($type === 'spot') {
                $assets = $assetModel->getAllSpot();
            } else if ($type === 'futures') {
                $assets = $assetModel->getAllFutures();
            } else {
                $assets = $assetModel->findAll();
            }
            
            $response = ['success' => true, 'assets' => $assets];
        }
        // 路由: /api/assets/{id} [GET]
        else if ($requestMethod === 'GET' && isset($pathParts[1]) && is_numeric($pathParts[1])) {
            $asset = $assetModel->findById($pathParts[1]);
            if ($asset) {
                $response = ['success' => true, 'asset' => $asset];
            } else {
                $response = ['success' => false, 'message' => '资产不存在'];
            }
        }
        // 路由: /api/assets/add [POST]
        else if ($requestMethod === 'POST' && isset($pathParts[1]) && $pathParts[1] === 'add') {
            if (!isset($params['name']) || !isset($params['type']) || !isset($params['price'])) {
                $response = ['success' => false, 'message' => '缺少必要参数'];
            } else {
                $response = $assetModel->addAsset($params['name'], $params['type'], $params['price']);
            }
        }
        break;
        
    case 'orders':
        $orderModel = new OrderModel();
        
        // 路由: /api/orders/create [POST]
        if ($requestMethod === 'POST' && isset($pathParts[1]) && $pathParts[1] === 'create') {
            if (!isset($params['user_id']) || !isset($params['asset_pair']) || 
                !isset($params['type']) || !isset($params['direction']) || 
                !isset($params['price']) || !isset($params['quantity'])) {
                $response = ['success' => false, 'message' => '缺少必要参数'];
            } else {
                $response = $orderModel->createOrder(
                    $params['user_id'],
                    $params['asset_pair'],
                    $params['type'],
                    $params['direction'],
                    $params['price'],
                    $params['quantity']
                );
            }
        }
        // 路由: /api/orders/cancel/{id} [POST]
        else if ($requestMethod === 'POST' && isset($pathParts[1]) && $pathParts[1] === 'cancel' && isset($pathParts[2])) {
            if (!isset($params['user_id'])) {
                $response = ['success' => false, 'message' => '缺少用户ID参数'];
            } else {
                $response = $orderModel->cancelOrder($pathParts[2], $params['user_id']);
            }
        }
        // 路由: /api/orders/user/{userId} [GET]
        else if ($requestMethod === 'GET' && isset($pathParts[1]) && $pathParts[1] === 'user' && isset($pathParts[2])) {
            $status = $params['status'] ?? null;
            $limit = $params['limit'] ?? 20;
            $offset = $params['offset'] ?? 0;
            
            $orders = $orderModel->getUserOrders($pathParts[2], $status, $limit, $offset);
            $response = ['success' => true, 'orders' => $orders];
        }
        break;
        
    case 'kline':
        $klineModel = new KlineModel();
        
        // 路由: /api/kline/{assetPair} [GET]
        if ($requestMethod === 'GET' && isset($pathParts[1])) {
            $assetPair = $pathParts[1];
            $startTime = $params['start'] ?? null;
            $endTime = $params['end'] ?? null;
            $limit = $params['limit'] ?? 100;
            
            if ($startTime && $endTime) {
                $klineData = $klineModel->getKlineData($assetPair, $startTime, $endTime, $limit);
            } else {
                $klineData = $klineModel->getLatestKline($assetPair, $limit);
            }
            
            $response = ['success' => true, 'kline' => $klineData];
        }
        // 路由: /api/kline/add [POST]
        else if ($requestMethod === 'POST' && isset($pathParts[1]) && $pathParts[1] === 'add') {
            if (!isset($params['asset_pair']) || !isset($params['timestamp']) || 
                !isset($params['open']) || !isset($params['high']) || 
                !isset($params['low']) || !isset($params['close'])) {
                $response = ['success' => false, 'message' => '缺少必要参数'];
            } else {
                $response = $klineModel->addKlineData(
                    $params['asset_pair'],
                    $params['timestamp'],
                    $params['open'],
                    $params['high'],
                    $params['low'],
                    $params['close'],
                    $params['volume'] ?? 0
                );
            }
        }
        break;
        
    case 'posts':
        $postModel = new PostModel();
        
        // 路由: /api/posts/create [POST]
        if ($requestMethod === 'POST' && isset($pathParts[1]) && $pathParts[1] === 'create') {
            if (!isset($params['user_id']) || !isset($params['content'])) {
                $response = ['success' => false, 'message' => '缺少必要参数'];
            } else {
                $assetPair = $params['asset_pair'] ?? null;
                $response = $postModel->createPost($params['user_id'], $params['content'], $assetPair);
            }
        }
        // 路由: /api/posts/delete/{id} [POST]
        else if ($requestMethod === 'POST' && isset($pathParts[1]) && $pathParts[1] === 'delete' && isset($pathParts[2])) {
            if (!isset($params['user_id'])) {
                $response = ['success' => false, 'message' => '缺少用户ID参数'];
            } else {
                $response = $postModel->deletePost($pathParts[2], $params['user_id']);
            }
        }
        // 路由: /api/posts/user/{userId} [GET]
        else if ($requestMethod === 'GET' && isset($pathParts[1]) && $pathParts[1] === 'user' && isset($pathParts[2])) {
            $limit = $params['limit'] ?? 20;
            $offset = $params['offset'] ?? 0;
            
            $posts = $postModel->getUserPosts($pathParts[2], $limit, $offset);
            $response = ['success' => true, 'posts' => $posts];
        }
        // 路由: /api/posts/asset/{assetPair} [GET]
        else if ($requestMethod === 'GET' && isset($pathParts[1]) && $pathParts[1] === 'asset' && isset($pathParts[2])) {
            $limit = $params['limit'] ?? 20;
            $offset = $params['offset'] ?? 0;
            
            $posts = $postModel->getAssetPairPosts($pathParts[2], $limit, $offset);
            $response = ['success' => true, 'posts' => $posts];
        }
        // 路由: /api/posts [GET]
        else if ($requestMethod === 'GET' && !isset($pathParts[1])) {
            $limit = $params['limit'] ?? 20;
            $offset = $params['offset'] ?? 0;
            
            $posts = $postModel->getLatestPosts($limit, $offset);
            $response = ['success' => true, 'posts' => $posts];
        }
        break;
        
    default:
        // 默认路由: /api
        if (empty($resourceType)) {
            $response = [
                'success' => true,
                'message' => '交易所API',
                'version' => '1.0.0',
                'endpoints' => [
                    'users' => '/api/users',
                    'assets' => '/api/assets',
                    'orders' => '/api/orders',
                    'kline' => '/api/kline',
                    'posts' => '/api/posts'
                ]
            ];
        }
        break;
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE); 