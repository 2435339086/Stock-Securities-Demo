<?php
require_once __DIR__ . '/../Model.php';

/**
 * 用户模型类
 */
class UserModel extends Model {
    protected $table = 'users';
    
    /**
     * 通过用户名查找用户
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username";
        return $this->db->selectOne($sql, [':username' => $username]);
    }
    
    /**
     * 注册新用户
     */
    public function register($username, $password) {
        // 检查用户名是否已存在
        if ($this->findByUsername($username)) {
            return ['success' => false, 'message' => '用户名已存在'];
        }
        
        // 密码哈希
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $this->create([
            'username' => $username,
            'password_hash' => $passwordHash,
            'balance' => 0,
            'register_time' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => '注册成功'
        ];
    }
    
    /**
     * 用户登录
     */
    public function login($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return ['success' => false, 'message' => '用户名不存在'];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => '密码错误'];
        }
        
        return [
            'success' => true,
            'user' => $user,
            'message' => '登录成功'
        ];
    }
    
    /**
     * 更新用户余额
     */
    public function updateBalance($userId, $amount) {
        $user = $this->findById($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }
        
        $newBalance = $user['balance'] + $amount;
        
        // 余额不能为负
        if ($newBalance < 0) {
            return ['success' => false, 'message' => '余额不足'];
        }
        
        $this->update($userId, ['balance' => $newBalance]);
        
        return [
            'success' => true,
            'new_balance' => $newBalance,
            'message' => '余额更新成功'
        ];
    }
} 