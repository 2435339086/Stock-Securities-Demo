<?php
/**
 * 用户注册页面
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 表单验证
    if (empty($username)) {
        $errors[] = '用户名不能为空';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = '用户名长度必须在3-50个字符之间';
    }
    
    if (empty($password)) {
        $errors[] = '密码不能为空';
    } elseif (strlen($password) < 6) {
        $errors[] = '密码长度不能少于6个字符';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = '两次输入的密码不一致';
    }
    
    // 提交注册
    if (empty($errors)) {
        $userModel = new UserModel();
        $result = $userModel->register($username, $password);
        
        if ($result['success']) {
            // 注册成功，跳转到登录页面
            $_SESSION['registration_success'] = true;
            header('Location: login.php');
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
    <title>用户注册</title>
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
    <h1>用户注册</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="post" action="register.php">
        <div>
            <label for="username">用户名：</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
        
        <div>
            <label for="password">密码：</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div>
            <label for="confirm_password">确认密码：</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <div>
            <button type="submit">注册</button>
        </div>
        
        <p>已有账号？<a href="login.php">点击登录</a></p>
    </form>
</body>
</html> 