<?php
require_once __DIR__ . '/../Model.php';

/**
 * 合约交易模型类
 */
class FuturesModel extends Model {
    protected $table = 'futures_positions';
    
    // 杠杆倍数
    const LEVERAGE = 10;
    
    // 保证金率
    const MARGIN_RATE = 0.1; // 10%
    
    // 强平保证金率
    const LIQUIDATION_MARGIN_RATE = 0.05; // 5%
    
    /**
     * 开仓
     */
    public function openPosition($userId, $assetPair, $direction, $price, $quantity) {
        // 计算仓位价值
        $positionValue = $price * $quantity;
        
        // 计算所需保证金
        $requiredMargin = $positionValue * self::MARGIN_RATE;
        
        // 检查用户余额
        $userModel = new UserModel();
        $user = $userModel->findById($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }
        
        if ($user['balance'] < $requiredMargin) {
            return ['success' => false, 'message' => '保证金不足'];
        }
        
        // 计算强平价格
        $liquidationPrice = $this->calculateLiquidationPrice($price, $direction);
        
        // 创建仓位记录
        $positionId = $this->create([
            'user_id' => $userId,
            'asset_pair' => $assetPair,
            'direction' => $direction,
            'entry_price' => $price,
            'quantity' => $quantity,
            'margin' => $requiredMargin,
            'liquidation_price' => $liquidationPrice,
            'status' => 'open',
            'create_time' => date('Y-m-d H:i:s')
        ]);
        
        // 扣除用户保证金
        $userModel->updateBalance($userId, -$requiredMargin);
        
        return [
            'success' => true,
            'position_id' => $positionId,
            'message' => '开仓成功',
            'data' => [
                'position_value' => $positionValue,
                'required_margin' => $requiredMargin,
                'liquidation_price' => $liquidationPrice
            ]
        ];
    }
    
    /**
     * 计算强平价格
     */
    private function calculateLiquidationPrice($entryPrice, $direction) {
        if ($direction === 'long') {
            // 做多强平价格 = 入场价格 * (1 - 保证金率)
            return $entryPrice * (1 - self::LIQUIDATION_MARGIN_RATE);
        } else {
            // 做空强平价格 = 入场价格 * (1 + 保证金率)
            return $entryPrice * (1 + self::LIQUIDATION_MARGIN_RATE);
        }
    }
    
    /**
     * 检查是否需要强平
     */
    public function checkLiquidation($positionId, $currentPrice) {
        $position = $this->findById($positionId);
        
        if (!$position) {
            return ['success' => false, 'message' => '仓位不存在'];
        }
        
        if ($position['status'] !== 'open') {
            return ['success' => false, 'message' => '仓位已关闭'];
        }
        
        $needLiquidation = false;
        
        if ($position['direction'] === 'long') {
            // 做多：当前价格 <= 强平价格
            $needLiquidation = $currentPrice <= $position['liquidation_price'];
        } else {
            // 做空：当前价格 >= 强平价格
            $needLiquidation = $currentPrice >= $position['liquidation_price'];
        }
        
        if ($needLiquidation) {
            return $this->liquidatePosition($positionId, $currentPrice);
        }
        
        return ['success' => false, 'message' => '无需强平'];
    }
    
    /**
     * 强平仓位
     */
    private function liquidatePosition($positionId, $currentPrice) {
        $position = $this->findById($positionId);
        
        // 计算盈亏
        $pnl = $this->calculatePnL($position, $currentPrice);
        
        // 更新仓位状态
        $this->update($positionId, [
            'status' => 'liquidated',
            'exit_price' => $currentPrice,
            'pnl' => $pnl,
            'close_time' => date('Y-m-d H:i:s')
        ]);
        
        // 更新用户余额
        $userModel = new UserModel();
        $userModel->updateBalance($position['user_id'], $pnl);
        
        return [
            'success' => true,
            'message' => '仓位已强平',
            'data' => [
                'position_id' => $positionId,
                'exit_price' => $currentPrice,
                'pnl' => $pnl
            ]
        ];
    }
    
    /**
     * 计算盈亏
     */
    private function calculatePnL($position, $currentPrice) {
        $positionValue = $position['entry_price'] * $position['quantity'];
        $currentValue = $currentPrice * $position['quantity'];
        
        if ($position['direction'] === 'long') {
            // 做多：当前价值 - 入场价值
            return $currentValue - $positionValue;
        } else {
            // 做空：入场价值 - 当前价值
            return $positionValue - $currentValue;
        }
    }
    
    /**
     * 获取用户的未平仓仓位
     */
    public function getOpenPositions($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND status = 'open'";
        return $this->db->select($sql, [':user_id' => $userId]);
    }
    
    /**
     * 获取用户的已平仓仓位
     */
    public function getClosedPositions($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND status != 'open'";
        return $this->db->select($sql, [':user_id' => $userId]);
    }
} 