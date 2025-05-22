<?php
require_once __DIR__ . '/../Model.php';

/**
 * 社区帖子模型类
 */
class PostModel extends Model {
    protected $table = 'posts';
    protected $primaryKey = 'post_id';
    
    /**
     * 创建新帖子
     */
    public function createPost($userId, $content, $assetPair = null) {
        $data = [
            'user_id' => $userId,
            'content' => $content,
            'publish_time' => date('Y-m-d H:i:s')
        ];
        
        if ($assetPair) {
            $data['asset_pair'] = $assetPair;
        }
        
        $postId = $this->create($data);
        
        return [
            'success' => true,
            'post_id' => $postId,
            'message' => '帖子发布成功'
        ];
    }
    
    /**
     * 删除帖子
     */
    public function deletePost($postId, $userId) {
        $post = $this->findById($postId);
        
        if (!$post) {
            return ['success' => false, 'message' => '帖子不存在'];
        }
        
        if ($post['user_id'] != $userId) {
            return ['success' => false, 'message' => '无权删除此帖子'];
        }
        
        $this->delete($postId);
        
        return [
            'success' => true,
            'message' => '帖子删除成功'
        ];
    }
    
    /**
     * 获取用户的所有帖子
     */
    public function getUserPosts($userId, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username 
                FROM {$this->table} p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.user_id = :user_id 
                ORDER BY p.publish_time DESC 
                LIMIT :limit OFFSET :offset";
                
        return $this->db->select($sql, [
            ':user_id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }
    
    /**
     * 获取特定资产对的帖子
     */
    public function getAssetPairPosts($assetPair, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username 
                FROM {$this->table} p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.asset_pair = :asset_pair 
                ORDER BY p.publish_time DESC 
                LIMIT :limit OFFSET :offset";
                
        return $this->db->select($sql, [
            ':asset_pair' => $assetPair,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }
    
    /**
     * 获取最新帖子
     */
    public function getLatestPosts($limit = 20, $offset = 0) {
        $sql = "SELECT p.*, u.username 
                FROM {$this->table} p 
                JOIN users u ON p.user_id = u.id 
                ORDER BY p.publish_time DESC 
                LIMIT :limit OFFSET :offset";
                
        return $this->db->select($sql, [
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }
} 