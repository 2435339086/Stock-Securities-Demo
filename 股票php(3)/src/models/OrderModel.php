<?php
require_once __DIR__ . '/../Model.php';

/**
 * 订单模型类
 */
class OrderModel extends Model {
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    
    /**
     * 创建新订单
     */
    public function createOrder($userId, $assetPair, $type, $direction, $price, $quantity) {
        // 生成唯一订单ID
        $orderId = $this->generateOrderId();
        
        $orderData = [
            'order_id' => $orderId,
            'user_id' => $userId,
            'asset_pair' => $assetPair,
            'type' => $type,
            'direction' => $direction,
            'price' => $price,
            'quantity' => $quantity,
            'status' => 'pending',
            'create_time' => date('Y-m-d H:i:s')
        ];
        
        $this->create($orderData);
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'message' => '订单创建成功'
        ];
    }
    
    /**
     * 取消订单
     */
    public function cancelOrder($orderId, $userId) {
        $order = $this->findById($orderId);
        
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }
        
        if ($order['user_id'] != $userId) {
            return ['success' => false, 'message' => '无权操作此订单'];
        }
        
        if ($order['status'] != 'pending') {
            return ['success' => false, 'message' => '只能取消未成交的订单'];
        }
        
        $this->update($orderId, ['status' => 'canceled']);
        
        return [
            'success' => true,
            'message' => '订单取消成功'
        ];
    }
    
    /**
     * 管理员取消订单
     */
    public function adminCancelOrder($orderId) {
        $order = $this->findById($orderId);
        
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }
        
        if ($order['status'] != 'pending') {
            return ['success' => false, 'message' => '只能取消未成交的订单'];
        }
        
        $this->update($orderId, ['status' => 'canceled']);
        
        // 记录管理员操作日志
        $this->logAdminAction('cancel_order', "管理员取消了订单 {$orderId}");
        
        return [
            'success' => true,
            'message' => '订单已强制取消'
        ];
    }
    
    /**
     * 完成订单
     */
    public function fillOrder($orderId) {
        $order = $this->findById($orderId);
        
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在'];
        }
        
        if ($order['status'] != 'pending') {
            return ['success' => false, 'message' => '订单已经处理过'];
        }
        
        $this->update($orderId, ['status' => 'filled']);
        
        return [
            'success' => true,
            'message' => '订单完成成功'
        ];
    }
    
    /**
     * 获取用户的订单列表
     */
    public function getUserOrders($userId, $status = null, $limit = 20, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY create_time DESC LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * 生成唯一订单ID
     */
    private function generateOrderId() {
        return 'ORD' . time() . rand(1000, 9999);
    }
    
    /**
     * 记录管理员操作日志
     */
    private function logAdminAction($actionType, $details) {
        $sql = "INSERT INTO admin_logs (action_type, details, create_time) VALUES (:action_type, :details, NOW())";
        $this->db->query($sql, [
            ':action_type' => $actionType,
            ':details' => $details
        ]);
    }
} 