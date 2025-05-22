<?php
/**
 * 资产讨论区页面
 */
session_start();
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/models/PostModel.php';
require_once __DIR__ . '/../src/models/AssetModel.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 获取资产列表
$assetModel = new AssetModel();
$assets = $assetModel->findAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>资产讨论区</title>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            display: flex;
            gap: 20px;
        }
        .post-form {
            flex: 1;
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
        }
        .post-list {
            flex: 2;
        }
        .post-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }
        .post-content {
            margin-bottom: 10px;
        }
        .post-footer {
            color: #999;
            font-size: 0.9em;
        }
        textarea {
            width: 100%;
            height: 100px;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .error-message {
            color: red;
            margin-bottom: 10px;
        }
        .success-message {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>资产讨论区</h1>
    
    <div class="container">
        <div class="post-form">
            <h2>发布新帖子</h2>
            <div id="message"></div>
            <form id="postForm">
                <select name="asset_pair" required>
                    <option value="">选择资产对</option>
                    <?php foreach ($assets as $asset): ?>
                    <option value="<?php echo htmlspecialchars($asset['name']); ?>">
                        <?php echo htmlspecialchars($asset['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <textarea name="content" placeholder="输入帖子内容..." required></textarea>
                
                <button type="submit">发布</button>
            </form>
        </div>
        
        <div class="post-list">
            <h2>最新帖子</h2>
            <div id="posts"></div>
        </div>
    </div>

    <script>
        // 加载帖子列表
        function loadPosts() {
            $.get('/api/posts', function(response) {
                if (response.success) {
                    const posts = response.posts;
                    let html = '';
                    
                    posts.forEach(post => {
                        html += `
                            <div class="post-item">
                                <div class="post-header">
                                    <span>${post.username}</span>
                                    <span>${post.asset_pair || '通用'}</span>
                                </div>
                                <div class="post-content">${post.content}</div>
                                <div class="post-footer">
                                    ${new Date(post.publish_time).toLocaleString()}
                                </div>
                            </div>
                        `;
                    });
                    
                    $('#posts').html(html);
                }
            });
        }
        
        // 提交新帖子
        $('#postForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                user_id: <?php echo $user_id; ?>,
                asset_pair: $('select[name="asset_pair"]').val(),
                content: $('textarea[name="content"]').val()
            };
            
            $.ajax({
                url: '/api/posts/create',
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        $('#message').html('<div class="success-message">发布成功！</div>');
                        $('#postForm')[0].reset();
                        loadPosts();
                    } else {
                        $('#message').html(`<div class="error-message">${response.message}</div>`);
                    }
                },
                error: function() {
                    $('#message').html('<div class="error-message">发布失败，请稍后重试</div>');
                }
            });
        });
        
        // 页面加载时加载帖子
        $(document).ready(function() {
            loadPosts();
            
            // 每30秒刷新一次帖子列表
            setInterval(loadPosts, 30000);
        });
    </script>
</body>
</html> 