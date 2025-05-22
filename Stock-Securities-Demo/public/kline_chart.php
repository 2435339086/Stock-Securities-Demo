<!DOCTYPE html>
<html>
<head>
    <title>K线图</title>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .chart-container {
            width: 100%;
            height: 600px;
            margin-bottom: 20px;
        }
        .controls {
            margin-bottom: 20px;
        }
        select, button {
            padding: 8px;
            margin-right: 10px;
        }
        .price-info {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .price-up {
            color: #f56c6c;
        }
        .price-down {
            color: #67c23a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>K线图</h1>
        
        <div class="controls">
            <select id="assetPair">
                <option value="BTC/USDT">BTC/USDT</option>
                <option value="ETH/USDT">ETH/USDT</option>
                <option value="BNB/USDT">BNB/USDT</option>
            </select>
            
            <select id="interval">
                <option value="1h">1小时</option>
                <option value="4h">4小时</option>
                <option value="1d">1天</option>
            </select>
            
            <button onclick="updateChart()">更新</button>
        </div>
        
        <div class="price-info">
            当前价格: <span id="currentPrice">--</span>
            <span id="priceChange"></span>
        </div>
        
        <div id="chart" class="chart-container"></div>
    </div>

    <script>
        let chart = null;
        let lastPrice = null;
        
        // 初始化图表
        function initChart() {
            chart = echarts.init(document.getElementById('chart'));
            
            const option = {
                title: {
                    text: 'K线图',
                    left: 'center'
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross'
                    }
                },
                xAxis: {
                    type: 'time',
                    scale: true
                },
                yAxis: {
                    type: 'value',
                    scale: true
                },
                series: [{
                    type: 'candlestick',
                    data: []
                }]
            };
            
            chart.setOption(option);
        }
        
        // 更新图表数据
        async function updateChart() {
            const assetPair = document.getElementById('assetPair').value;
            const interval = document.getElementById('interval').value;
            
            try {
                const response = await fetch(`/api/kline_generator.php?asset_pair=${assetPair}&interval=${interval}`);
                const data = await response.json();
                
                if (data.success) {
                    // 转换数据格式
                    const klineData = data.data.map(item => [
                        item.timestamp * 1000, // 转换为毫秒
                        item.open,
                        item.close,
                        item.low,
                        item.high
                    ]);
                    
                    // 更新图表
                    chart.setOption({
                        title: {
                            text: `${assetPair} ${interval} K线图`
                        },
                        series: [{
                            data: klineData
                        }]
                    });
                    
                    // 更新当前价格
                    const currentPrice = data.data[data.data.length - 1].close;
                    updatePrice(currentPrice);
                }
            } catch (error) {
                console.error('获取数据失败:', error);
            }
        }
        
        // 更新价格显示
        function updatePrice(price) {
            const priceElement = document.getElementById('currentPrice');
            const changeElement = document.getElementById('priceChange');
            
            if (lastPrice !== null) {
                const change = price - lastPrice;
                const changePercent = (change / lastPrice * 100).toFixed(2);
                
                changeElement.textContent = `(${change >= 0 ? '+' : ''}${changePercent}%)`;
                changeElement.className = change >= 0 ? 'price-up' : 'price-down';
            }
            
            priceElement.textContent = price.toFixed(2);
            lastPrice = price;
        }
        
        // 页面加载完成后初始化
        window.onload = function() {
            initChart();
            updateChart();
            
            // 自动更新（每5秒）
            setInterval(updateChart, 5000);
        };
        
        // 窗口大小改变时调整图表大小
        window.onresize = function() {
            chart && chart.resize();
        };
    </script>
</body>
</html> 