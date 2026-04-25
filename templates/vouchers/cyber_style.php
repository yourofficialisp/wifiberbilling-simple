<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #1a1a2e; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: #16213e; color: #e94560; padding: 30px; width: 400px; border: 2px solid #e94560; box-shadow: 0 0 30px rgba(233, 69, 96, 0.3); position: relative; }
        .voucher::before { content: ''; position: absolute; top: -2px; left: -2px; right: -2px; bottom: -2px; border: 2px solid #e94560; animation: glow 2s ease-in-out infinite; }
        @keyframes glow { 0%, 100% { box-shadow: 0 0 10px rgba(233, 69, 96, 0.3); } 50% { box-shadow: 0 0 30px rgba(233, 69, 96, 0.6); } }
        .voucher-header { text-align: center; border-bottom: 1px solid #e94560; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 24px; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .voucher-body .row .label { color: #e94560; }
        .voucher-body .row .value { color: #e94560; font-weight: bold; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e94560; }
        .voucher-footer .price { font-size: 28px; font-weight: bold; }
        .scan-line { position: absolute; top: 0; left: 0; width: 100%; height: 2px; background: #e94560; animation: scan 3s linear infinite; }
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="scan-line"></div>
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
        </div>
        <div class="voucher-body">
            <div class="row">
                <span class="label">USERNAME:</span>
                <span class="value">{{username}}</span>
            </div>
            <div class="row">
                <span class="label">PASSWORD:</span>
                <span class="value">{{password}}</span>
            </div>
            <div class="row">
                <span class="label">DURATION:</span>
                <span class="value">{{validity}}</span>
            </div>
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
        </div>
    </div>
</body>
</html>
