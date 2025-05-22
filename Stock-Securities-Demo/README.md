# 交易所数据库和API

这是一个简单的交易所后端系统，包含数据库设计和基本API接口。

## 数据库结构

- **用户表（users）**：id, 用户名, 密码哈希, 余额, 注册时间
- **资产表（assets）**：id, 名称（如BTC/USDT）, 类型（现货/合约）, 当前价格
- **订单表（orders）**：订单ID, 用户ID, 资产对, 类型（限价/市价）, 方向（买/卖）, 价格, 数量, 状态（未成交/已成交/已取消）
- **K线数据表（kline）**：资产对, 时间戳, 开盘价, 最高价, 最低价, 收盘价
- **社区帖子表（posts）**：帖子ID, 用户ID, 资产对, 内容, 发布时间

## 安装说明

1. 确保已安装PHP 7.0+和MySQL 5.7+
2. 将项目文件上传到Web服务器
3. 修改 `config/database.php` 中的数据库连接信息
4. 运行 `php init_database.php` 初始化数据库结构

## API接口

### 用户相关

- `POST /api/users/register` - 注册新用户
- `POST /api/users/login` - 用户登录
- `GET /api/users/{id}` - 获取用户信息

### 资产相关

- `GET /api/assets` - 获取所有资产
- `GET /api/assets?type=spot` - 获取所有现货资产
- `GET /api/assets?type=futures` - 获取所有合约资产
- `GET /api/assets/{id}` - 获取特定资产信息
- `POST /api/assets/add` - 添加新资产

### 订单相关

- `POST /api/orders/create` - 创建新订单
- `POST /api/orders/cancel/{id}` - 取消订单
- `GET /api/orders/user/{userId}` - 获取用户的订单列表

### K线数据

- `GET /api/kline/{assetPair}` - 获取资产对的K线数据
- `GET /api/kline/{assetPair}?start=1609459200&end=1609545600` - 获取特定时间范围的K线数据
- `POST /api/kline/add` - 添加K线数据点

### 社区帖子

- `GET /api/posts` - 获取最新帖子
- `GET /api/posts/user/{userId}` - 获取指定用户的帖子
- `GET /api/posts/asset/{assetPair}` - 获取特定资产对的帖子
- `POST /api/posts/create` - 创建新帖子
- `POST /api/posts/delete/{id}` - 删除帖子

## 示例请求

### 注册用户

```
POST /api/users/register
Content-Type: application/json

{
  "username": "testuser",
  "password": "password123"
}
```

### 登录

```
POST /api/users/login
Content-Type: application/json

{
  "username": "testuser",
  "password": "password123"
}
```

### 创建订单

```
POST /api/orders/create
Content-Type: application/json

{
  "user_id": 1,
  "asset_pair": "BTC/USDT",
  "type": "limit",
  "direction": "buy",
  "price": 65000,
  "quantity": 0.1
}
```