<?php
session_start();

// 检查是否已安装
if (file_exists('config/installed.lock')) {
    die('系统已安装，如需重新安装请删除 config/installed.lock 文件');
}

// 处理安装步骤
$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1: // 环境检查
            $step = 2;
            break;
            
        case 2: // 数据库配置
            $dbConfig = [
                'host' => $_POST['db_host'] ?? '',
                'dbname' => $_POST['db_name'] ?? '',
                'username' => $_POST['db_user'] ?? '',
                'password' => $_POST['db_pass'] ?? '',
                'charset' => 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ];
            
            try {
                // 验证输入
                if (empty($dbConfig['host']) || empty($dbConfig['dbname']) || 
                    empty($dbConfig['username']) || empty($dbConfig['password'])) {
                    throw new Exception('所有数据库配置项都必须填写');
                }

                // 测试数据库连接
                $dsn = "mysql:host={$dbConfig['host']};charset={$dbConfig['charset']}";
                $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
                
                // 检查MySQL版本
                $version = $pdo->query('select version()')->fetchColumn();
                if (version_compare($version, '5.7.0', '<')) {
                    throw new Exception('MySQL版本必须 >= 5.7.0，当前版本：' . $version);
                }
                
                // 创建数据库
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // 选择数据库
                $pdo->exec("USE `{$dbConfig['dbname']}`");
                
                // 检查数据库权限
                $pdo->exec("SHOW TABLES");
                
                // 保存配置
                if (!is_dir('config')) {
                    if (!mkdir('config', 0755, true)) {
                        throw new Exception('无法创建config目录，请检查目录权限');
                    }
                }
                
                $configContent = '<?php return ' . var_export($dbConfig, true) . ';';
                if (file_put_contents('config/database.php', $configContent) === false) {
                    throw new Exception('无法写入数据库配置文件，请检查文件权限');
                }
                
                $step = 3;
            } catch (PDOException $e) {
                $error = '数据库连接失败: ' . $e->getMessage();
                // 记录详细错误信息
                error_log('数据库连接错误: ' . $e->getMessage());
            } catch (Exception $e) {
                $error = $e->getMessage();
                // 记录详细错误信息
                error_log('安装错误: ' . $e->getMessage());
            }
            break;
            
        case 3: // 初始化数据库
            try {
                require_once 'init_database.php';
                $step = 4;
            } catch (Exception $e) {
                $error = '数据库初始化失败: ' . $e->getMessage();
            }
            break;
            
        case 4: // 完成安装
            // 创建安装锁定文件
            file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
            $success = '安装完成！';
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>交易所系统安装向导</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .step {
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .step.active {
            background: #e9ecef;
            border-left: 4px solid #007bff;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .success {
            color: #28a745;
            margin-bottom: 15px;
        }
        .check-item {
            margin-bottom: 10px;
        }
        .check-item.success {
            color: #28a745;
        }
        .check-item.error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>交易所系统安装向导</h1>
        
        <div class="step <?php echo $step == 1 ? 'active' : ''; ?>">
            <h2>步骤1：环境检查</h2>
            <?php if ($step == 1): ?>
                <?php
                $checks = [
                    'PHP版本 >= 7.0' => version_compare(PHP_VERSION, '7.0.0', '>='),
                    'PDO扩展' => extension_loaded('pdo'),
                    'PDO MySQL扩展' => extension_loaded('pdo_mysql'),
                    'config目录可写' => is_writable('config') || is_writable('.')
                ];
                
                $allPassed = true;
                foreach ($checks as $check => $result) {
                    $allPassed = $allPassed && $result;
                    echo "<div class='check-item " . ($result ? 'success' : 'error') . "'>";
                    echo $check . ': ' . ($result ? '通过' : '未通过');
                    echo "</div>";
                }
                ?>
                
                <?php if ($allPassed): ?>
                    <form method="post">
                        <button type="submit">下一步</button>
                    </form>
                <?php else: ?>
                    <div class="error">请解决以上问题后继续安装</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="step <?php echo $step == 2 ? 'active' : ''; ?>">
            <h2>步骤2：数据库配置</h2>
            <?php if ($step == 2): ?>
                <?php if ($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label for="db_host">数据库主机</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">数据库名</label>
                        <input type="text" id="db_name" name="db_name" value="exchange" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">数据库用户名</label>
                        <input type="text" id="db_user" name="db_user" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">数据库密码</label>
                        <input type="password" id="db_pass" name="db_pass" required>
                    </div>
                    
                    <button type="submit">下一步</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="step <?php echo $step == 3 ? 'active' : ''; ?>">
            <h2>步骤3：初始化数据库</h2>
            <?php if ($step == 3): ?>
                <?php if ($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <p>点击下一步开始初始化数据库...</p>
                    <button type="submit">下一步</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="step <?php echo $step == 4 ? 'active' : ''; ?>">
            <h2>步骤4：完成安装</h2>
            <?php if ($step == 4): ?>
                <?php if ($success): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <p>系统安装完成！</p>
                    <p>默认管理员账号：admin</p>
                    <p>默认管理员密码：admin123</p>
                    <p>请及时修改默认密码！</p>
                    
                    <a href="public/login.php" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">进入登录页面</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 