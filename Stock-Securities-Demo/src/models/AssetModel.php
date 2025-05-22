<?php
require_once __DIR__ . '/../Model.php';

/**
 * 资产模型类
 */
class AssetModel extends Model {
    protected $table = 'assets';
    
    /**
     * 根据名称查找资产
     */
    public function findByName($name) {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        return $this->db->selectOne($sql, [':name' => $name]);
    }
    
    /**
     * 更新资产价格
     */
    public function updatePrice($assetId, $newPrice) {
        return $this->update($assetId, ['current_price' => $newPrice]);
    }
    
    /**
     * 更新资产波动率
     */
    public function updateVolatility($assetPair, $volatility) {
        $asset = $this->findByName($assetPair);
        
        if (!$asset) {
            return ['success' => false, 'message' => '资产不存在'];
        }
        
        // 更新资产波动率设置
        $sql = "UPDATE {$this->table} SET volatility = :volatility WHERE name = :name";
        $this->db->query($sql, [
            ':volatility' => $volatility,
            ':name' => $assetPair
        ]);
        
        return [
            'success' => true,
            'message' => '波动率更新成功',
            'data' => [
                'asset_pair' => $assetPair,
                'volatility' => $volatility
            ]
        ];
    }
    
    /**
     * 获取所有现货资产
     */
    public function getAllSpot() {
        $sql = "SELECT * FROM {$this->table} WHERE type = 'spot'";
        return $this->db->select($sql);
    }
    
    /**
     * 获取所有合约资产
     */
    public function getAllFutures() {
        $sql = "SELECT * FROM {$this->table} WHERE type = 'futures'";
        return $this->db->select($sql);
    }
    
    /**
     * 添加新资产
     */
    public function addAsset($name, $type, $initialPrice) {
        // 检查资产是否已存在
        if ($this->findByName($name)) {
            return ['success' => false, 'message' => '资产已存在'];
        }
        
        $assetId = $this->create([
            'name' => $name,
            'type' => $type,
            'current_price' => $initialPrice,
            'volatility' => 5.0 // 默认波动率5%
        ]);
        
        return [
            'success' => true,
            'asset_id' => $assetId,
            'message' => '资产添加成功'
        ];
    }
} 