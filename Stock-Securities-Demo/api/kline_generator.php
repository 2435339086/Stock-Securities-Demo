<?php
/**
 * K线数据生成器API
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/models/AssetModel.php';

// 获取请求参数
$assetPair = $_GET['asset_pair'] ?? '';
$interval = $_GET['interval'] ?? '1h'; // 默认1小时
$limit = min((int)($_GET['limit'] ?? 100), 1000); // 限制最大返回数量

// 验证参数
if (empty($assetPair)) {
    echo json_encode([
        'success' => false,
        'message' => '缺少资产对参数'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 验证时间间隔
$validIntervals = ['1h', '4h', '1d'];
if (!in_array($interval, $validIntervals)) {
    echo json_encode([
        'success' => false,
        'message' => '无效的时间间隔'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 获取资产当前价格
    $assetModel = new AssetModel();
    $asset = $assetModel->findByName($assetPair);
    
    if (!$asset) {
        echo json_encode([
            'success' => false,
            'message' => '资产对不存在'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $basePrice = $asset['current_price'];
    $klineData = [];
    
    // 生成K线数据
    $currentTime = time();
    $intervalSeconds = [
        '1h' => 3600,
        '4h' => 14400,
        '1d' => 86400
    ][$interval];
    
    for ($i = 0; $i < $limit; $i++) {
        // 计算时间戳
        $timestamp = $currentTime - ($i * $intervalSeconds);
        
        // 生成价格波动范围（±5%）
        $priceChange = $basePrice * (rand(-500, 500) / 10000);
        $openPrice = $basePrice + $priceChange;
        
        // 生成最高价和最低价（±2%）
        $highPrice = $openPrice * (1 + (rand(0, 200) / 10000));
        $lowPrice = $openPrice * (1 - (rand(0, 200) / 10000));
        
        // 生成收盘价
        $closePrice = $openPrice * (1 + (rand(-200, 200) / 10000));
        
        // 生成成交量（随机值）
        $volume = rand(100, 10000) / 100;
        
        $klineData[] = [
            'timestamp' => $timestamp,
            'open' => round($openPrice, 2),
            'high' => round($highPrice, 2),
            'low' => round($lowPrice, 2),
            'close' => round($closePrice, 2),
            'volume' => round($volume, 2)
        ];
        
        // 更新基准价格
        $basePrice = $closePrice;
    }
    
    // 按时间戳排序（从早到晚）
    usort($klineData, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });
    
    echo json_encode([
        'success' => true,
        'asset_pair' => $assetPair,
        'interval' => $interval,
        'data' => $klineData
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '生成K线数据失败: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} 