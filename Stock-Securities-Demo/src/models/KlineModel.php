<?php
require_once __DIR__ . '/../Model.php';

/**
 * K线数据模型类
 */
class KlineModel extends Model {
    protected $table = 'kline';
    
    /**
     * 添加K线数据
     */
    public function addKlineData($assetPair, $timestamp, $open, $high, $low, $close, $volume = 0) {
        $data = [
            'asset_pair' => $assetPair,
            'timestamp' => $timestamp,
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'volume' => $volume
        ];
        
        // 检查是否已存在相同的数据点
        $existing = $this->getKlineByTimestamp($assetPair, $timestamp);
        
        if ($existing) {
            // 更新现有记录
            $sql = "UPDATE {$this->table} SET open = :open, high = :high, low = :low, close = :close, volume = :volume 
                    WHERE asset_pair = :asset_pair AND timestamp = :timestamp";
            $this->db->update($sql, [
                ':asset_pair' => $assetPair,
                ':timestamp' => $timestamp,
                ':open' => $open,
                ':high' => $high,
                ':low' => $low,
                ':close' => $close,
                ':volume' => $volume
            ]);
            return ['success' => true, 'message' => 'K线数据已更新'];
        } else {
            // 创建新记录
            $sql = "INSERT INTO {$this->table} (asset_pair, timestamp, open, high, low, close, volume) 
                    VALUES (:asset_pair, :timestamp, :open, :high, :low, :close, :volume)";
            $this->db->query($sql, [
                ':asset_pair' => $assetPair,
                ':timestamp' => $timestamp,
                ':open' => $open,
                ':high' => $high,
                ':low' => $low,
                ':close' => $close,
                ':volume' => $volume
            ]);
            return ['success' => true, 'message' => 'K线数据已添加'];
        }
    }
    
    /**
     * 根据时间戳获取K线数据
     */
    public function getKlineByTimestamp($assetPair, $timestamp) {
        $sql = "SELECT * FROM {$this->table} WHERE asset_pair = :asset_pair AND timestamp = :timestamp";
        return $this->db->selectOne($sql, [':asset_pair' => $assetPair, ':timestamp' => $timestamp]);
    }
    
    /**
     * 获取指定时间范围内的K线数据
     */
    public function getKlineData($assetPair, $startTimestamp, $endTimestamp, $limit = 1000) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE asset_pair = :asset_pair 
                AND timestamp >= :start_timestamp 
                AND timestamp <= :end_timestamp 
                ORDER BY timestamp ASC
                LIMIT :limit";
                
        return $this->db->select($sql, [
            ':asset_pair' => $assetPair,
            ':start_timestamp' => $startTimestamp,
            ':end_timestamp' => $endTimestamp,
            ':limit' => $limit
        ]);
    }
    
    /**
     * 获取最新的K线数据
     */
    public function getLatestKline($assetPair, $limit = 100) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE asset_pair = :asset_pair 
                ORDER BY timestamp DESC 
                LIMIT :limit";
                
        $result = $this->db->select($sql, [':asset_pair' => $assetPair, ':limit' => $limit]);
        
        // 倒序返回，让时间从早到晚
        return array_reverse($result);
    }
} 