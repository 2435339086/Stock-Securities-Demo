<?php
/**
 * 用户登录页面
 */
session_start();
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/models/UserModel.php';

// 如果用户已登录，重定向到首页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$username = '';

// 检查是否有注册成功的消息
$registration_success = false;
if (isset($_SESSION['registration_success'])) {
    $registration_success = true;
    unset($_SESSION['registration_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // 表单验证
    if (empty($username)) {
        $errors[] = '用户名不能为空';
    }
    
    if (empty($password)) {
        $errors[] = '密码不能为空';
    }
    
    // 验证用户凭据
    if (empty($errors)) {
        $userModel = new UserModel();
        $result = $userModel->login($username, $password);
        
        if ($result['success']) {
            // 登录成功，创建会话
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            
            // 生成并存储一个会话令牌（可用于防止CSRF攻击）
            $token = bin2hex(random_bytes(32));
            $_SESSION['token'] = $token;
            
            // 重定向到首页
            header('Location: index.php');
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>用户登录</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
        .success-message {
            color: green;
            margin-bottom: 15px;
        }
        form div {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>用户登录</h1>
    
    <?php if ($registration_success): ?>
        <div class="success-message">
            <p>注册成功，请登录！</p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="post" action="login.php">
        <div>
            <label for="username">用户名：</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
        
        <div>
            <label for="password">密码：</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div>
            <button type="submit">登录</button>
        </div>
        
        <p>没有账号？<a href="register.php">点击注册</a></p>
    </form>
</body>
</html> 