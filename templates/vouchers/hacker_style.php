<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #000; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .voucher { background: #000; color: #0f0; padding: 25px; width: 380px; border: 2px solid #0f0; position: relative; }
        .voucher::before { content: ''; position: absolute; top: 5px; left: 5px; right: 5px; bottom: 5px; border: 1px solid #0f0; }
        .voucher-header { text-align: center; border-bottom: 1px solid #0f0; padding-bottom: 15px; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 24px; text-transform: uppercase; letter-spacing: 2px; animation: blink 1s infinite; }
        @keyframes blink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0.5; } }
        .voucher-body .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .voucher-body .row .label { color: #0f0; }
        .voucher-body .row .value { color: #0f0; font-weight: bold; }
        .voucher-footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #0f0; }
        .voucher-footer .price { font-size: 28px; font-weight: bold; color: #0f0; }
    </style>
</head>
<body>
    <div class="voucher">
        <div class="voucher-header">
            <h1>{{hotspotname}}</h1>
        </div>
        <div class="voucher-body">
            <div class="row">
                <span class="label">USER:</span>
                <span class="value">{{username}}</span>
            </div>
            <div class="row">
                <span class="label">PASS:</span>
                <span class="value">{{password}}</span>
            </div>
            <div class="row">
                <span class="label">TIME:</span>
                <span class="value">{{validity}}</span>
            </div>
            <div class="row">
                <span class="label">COST:</span>
                <span class="value">{{price}}</span>
            </div>
        </div>
        <div class="voucher-footer">
            <div class="price">{{price}}</div>
        </div>
    </div>
</body>
</html>
